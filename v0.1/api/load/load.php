        <?php
        require_once('../../assets/initializer.php');
        $data = (array) json_decode(file_get_contents('php://input'), true);

        $Load = new Load($db);

        #  Check for the request method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('HTTP/1.1 405 Method Not Allowed');
            header('Allow: POST');
            exit();
        }

        #  Check if load array is present and not empty
        if (empty($data['load'])) {
            $Load->outputData(false, 'load array is missing or empty', null);
            exit;
        }

        foreach ($data['load'] as $item) {
            $requiredParams = ['name', 'Qty', 'watts', 'hours_per_day', 'days_per_week', 'type'];
            foreach ($requiredParams as $param) {
                if (empty($item[$param])) {
                    $Load->outputData(false, "$param parameter is missing or empty", null);
                }
            }
        }

        $dataArrayLoadAc = [];
        $dataArrayLoadDc = [];
        foreach ($data['load'] as $load) {
            if ($load['type'] === 'AC') {
                $dataArrayLoadAc[] = $load;
            } else if ($load['type'] === 'DC') {
                $dataArrayLoadDc[] = $load;
            }
        }

        # 1A CLACULATE AC LOAD _________________________________________

        $totalAc = 0;
        $totalAC_inverter = 0;

        # Calculate total watts usage in the array of AC loads
        foreach ($dataArrayLoadAc as $item) {
            $totalAc += intval($item['Qty']) * intval($item['watts']) * intval($item['hours_per_day']) * intval($item['days_per_week']);
            $totalAC_inverter += intval($item['Qty']) * intval($item['watts']);
        }


        # 2B CLACULATE DC LOAD _________________________________________
        $totalDc = 0;
        $totalDC_inverter = 0;

        # Calculate total watts usage in the array of AC loads
        foreach ($dataArrayLoadDc as $item) {
            $totalDc += intval($item['Qty']) * intval($item['watts']) * intval($item['hours_per_day']) * intval($item['days_per_week']);
            $totalDC_inverter += intval($item['Qty']) * intval($item['watts']);
        }


        # acWeeklyWattsHours::Ac weekly watts hours..
        $acWeeklyWattsHours = ($totalAc == 0) ? (0) : ($totalAc);

        #Calculate total Ac weekly load ..

        $calculateTotalAcWeeklyLoad = ceil($acWeeklyWattsHours * $_ENV['acInEfficiencyFactor']);

        $totalDc = ($totalDc == 0) ? (0) : ($totalDc);

        # TotalDCWeekly load
        $calculateTotalDcWeeklyLoad = ceil($totalDc * 1.25);


        # 3A CLACULATE PV COUNTRIBUTION  _________________________________________

        $totalWeeklyLoad = ($calculateTotalAcWeeklyLoad  + $calculateTotalDcWeeklyLoad);


        $calculateBackupContributionPercentage = $Load->calculateBackupContributionPercentage($totalWeeklyLoad);

        $calculateAdjustedWeeklyLoad = $Load->calculateAdjustedWeeklyLoad($totalWeeklyLoad, $calculateBackupContributionPercentage);

        $calculateDailyPvEnergyBudget = $Load->calculateDailyPvEnergyBudget($calculateAdjustedWeeklyLoad);


        # 4A CONVERT TO AMP HOURS  _________________________________________

        $calculateTotalDailyAmpHours = $Load->calculateTotalDailyAmpHours($calculateDailyPvEnergyBudget, $_ENV['systemVolts']);

        # 5A CALCULATE PV ARRAY SIZE  _________________________________________

        $calculateArrayCurrentInAmps = $Load->calculateArrayCurrentInAmps($calculateTotalDailyAmpHours, $_ENV['brightSunshineHours']);

        $calculateModuleCurrentInAmps  = $Load->calculateModuleCurrentInAmps();

        $calculateNumberOfModuleInParallel = $Load->calculateNumberOfModuleInParallel($calculateArrayCurrentInAmps, $calculateModuleCurrentInAmps);

        $calculateNumberOfModuleInSeries = $Load->calculateNumberOfModuleInSeries();

        $calculatetotalNumberOfPvModules = $Load->calculatetotalNumberOfPvModules($calculateNumberOfModuleInSeries, $calculateNumberOfModuleInParallel);

        # 5B CALCULATE BATTERY SIZE  _________________________________________

        $normalStorageCapacityInAmps = $Load->calculateNormalStorageCapacityInAmps($calculateTotalDailyAmpHours, $_ENV['daysOfAutonomy']);

        $calculateRequiredBateryCapacityInAmpHours  = $Load->calculateRequiredBateryCapacityInAmpHours($normalStorageCapacityInAmps, $_ENV['maximumDrawDown']);

        #calculateTotalBatteryCapacityInAmpsHours::Single battery capacity n amp hours.

        $calculateTotalBatteryCapacityInAmpsHours = $Load->calculateTotalBatteryCapacityInAmpsHours($calculateRequiredBateryCapacityInAmpHours, $_ENV['factorForColdWater']);

        $calculateNumbersOfbatteryInParralel = $Load->calculateNumbersOfbatteryInParralel($calculateTotalBatteryCapacityInAmpsHours, $_ENV['singleBatteryCapacityInAmpHours']);

        $calculateNumbersOfbatteryInSeries = $Load->calculateNumbersOfbatteryInSeries();

        $calculateTotalNumbersOfBattery =  $Load->calculateTotalNumbersOfBattery($calculateNumbersOfbatteryInSeries, $calculateNumbersOfbatteryInParralel);


        #7B CALCULATE CHARGE CONTROLLER SIZE .....................................................................................|

        $calculateChargeControllerSize = $Load->calculateChargeControllerSize($calculateArrayCurrentInAmps);

        # 8b  CALCULATE PV MODULE FOR HYBRI RATIO  .....................................................................................|

        $calculateRecommendedPvModule =   $Load->calculateRecommendedPvModule($calculatetotalNumberOfPvModules);


        # 9b  Total Pv modules sunshine backup  .....................................................................................|

        $calculateTotalNumbersOfPvModules  = $Load->calculateTotalNumbersOfPvModules($calculatetotalNumberOfPvModules);

        # calculate inverter size
         $totalInverterSize = $totalDC_inverter + $totalAC_inverter;

         $totalDailyLoad = $totalWeeklyLoad / 7;

         $BatterySize = $Load->calculateBatterySize($totalDailyLoad);

         $getRecommendedInverter = $Load->getRecommendedInverter($totalInverterSize);

         $getRecommendedChargeController = $Load->getRecommendedChargeController($calculateChargeControllerSize);

         $getRecommendedBatteryPower = $Load->getRecommendedBatteryPower($BatterySize);

         $recommendedEquipment = is_array($getRecommendedInverter) && is_array($getRecommendedChargeController) && is_array($getRecommendedBatteryPower)
			? array_merge($getRecommendedInverter, $getRecommendedChargeController, $getRecommendedBatteryPower)
			: array_merge([], $getRecommendedInverter ?? [], $getRecommendedChargeController ?? [], $getRecommendedBatteryPower ?? []);


         $loadRespose = array(
			'inverterSize' => $totalInverterSize,
			'numbersOfBatteriesInSeries' => $calculateNumbersOfbatteryInSeries,
			'numbersOfBatteryInParallel' => $calculateNumbersOfbatteryInParralel,
			'numbersOfControllerRequired' => $calculateChargeControllerSize,
			'totalNumberOfPvModules' => $calculateTotalNumbersOfPvModules,
			'totalNumberOfBatteries' => $calculateTotalNumbersOfBattery,
			'totalWeeklyLoad' => $totalWeeklyLoad,
			'BatterySize' => $BatterySize,
			'numberOfPvModules' => $calculateTotalNumbersOfPvModules,
			'recommendedPvModules' => $calculateRecommendedPvModule,
			'recommendedProducts' => $recommendedEquipment
		);


        $Load->outputData(true, 'Fetched load ', $loadRespose);
