<?php




class Load extends AbstractClasses
{

    private   $conn;

    public function __construct(Database $database)
    {
        $this->conn = $database->connect();
    }


    #getRecommendedInverter::This method fetches all Recommended loand product
    public function getRecommendedInverter($productsize)
    {


        $sql = "SELECT *
    FROM tblproducts WHERE size >= $productsize AND pcat_id IN (
      SELECT id
      FROM tblcategory
      WHERE catname = 'Inverter'
    )
    ORDER BY tblproducts.id DESC;
    
    ";
        try {
            $stmt = $this->conn->query($sql);
            $stmt->execute();
            $count = $stmt->rowCount();
            if ($count === 0) {
                $_SESSION['err'] = "No record found";
                return [];
            }
            $dataArray = array();
            $recommendedInveter = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($recommendedInveter as $allInveter) {
                $array = [
                    'productname' => ($allInveter['pname']),
                    'productimage' => ($allInveter['imageUrl']),
                    'productprice' => $this->formatCurrency($allInveter['price']),
                    'productQuantity' => intval($allInveter['pquantity']),
                    'prductDesc' => ($allInveter['pdesc']),
                    'prductSize' => ($allInveter['size'])
                ];

                array_push($dataArray, $array);
            }
        } catch (PDOException $e) {
            $_SESSION['err'] = "Error while recommending inverter:" . $e->getMessage();
            return false;
        } finally {
            $stmt = null;
            // $this->conn = null;
        }
        return $dataArray;
    }


    #getRecommendedInverter::This method fetches all Recommended getRecommendedChargeController product
    public function getRecommendedChargeController($chargeController)
    {


        $sql = "SELECT *
    FROM tblproducts WHERE size >= $chargeController AND unit = 'AMPS' AND pcat_id IN (
      SELECT id
      FROM tblcategory
      WHERE catname = 'Chargecontroller'
    )
    ORDER BY tblproducts.id DESC;
    
    ";
        try {
            $stmt = $this->conn->query($sql);
            $stmt->execute();
            $count = $stmt->rowCount();
            if ($count === 0) {
                $_SESSION['err'] = "No record found";
                return [];
            }
            $dataArray = array();
            $recommendedInveter = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($recommendedInveter as $allInveter) {
                $array = [
                    'productname' => ($allInveter['pname']),
                    'productimage' => ($allInveter['imageUrl']),
                    'productprice' => $this->formatCurrency($allInveter['price']),
                    'productQuantity' => intval($allInveter['pquantity']),
                    'prductDesc' => ($allInveter['pdesc']),
                    'prductSize' => ($allInveter['size'])
                ];
                array_push($dataArray, $array);
            }
        } catch (PDOException $e) {
            $_SESSION['err'] = "Error while recommending chargecontroller:" . $e->getMessage();
            return false;
        } finally {
            $stmt = null;
            // $this->checkSize = null;
        }
        return $dataArray;
    }




    #getRecommendedBatteryPower::This method fetches all Recommended getRecommendedBatteryPower product
    public function getRecommendedBatteryPower($batterypower)
    {


        $sql = "SELECT *
    FROM tblproducts WHERE size >= $batterypower AND unit = 'AMPS' AND pcat_id IN (
      SELECT id
      FROM tblcategory
      WHERE catname = 'Battery'
    )
    ORDER BY tblproducts.id DESC;
    
    ";
        try {
            $stmt = $this->conn->query($sql);
            $stmt->execute();
            $count = $stmt->rowCount();
            if ($count === 0) {
                $_SESSION['err'] = "No record found";
                return [];
            }
            $dataArray = array();
            $recommendedInveter = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($recommendedInveter as $allInveter) {
                $array = [
                    'productname' => ($allInveter['pname']),
                    'productimage' => ($allInveter['imageUrl']),
                    'productprice' => $this->formatCurrency($allInveter['price']),
                    'productQuantity' => intval($allInveter['pquantity']),
                    'prductDesc' => ($allInveter['pdesc']),
                    'prductSize' => ($allInveter['size'])
                ];

                array_push($dataArray, $array);
            }
        } catch (PDOException $e) {
            $_SESSION['err'] = "Error while recommending chargecontroller:" . $e->getMessage();
            return false;
        } finally {
            $stmt = null;
            // $this->checkSize = null;
        }
        return $dataArray;
    }





    public function calculateBackupContributionPercentage($totalWeeklyLoad)
    {
        $backUpContributionPercentage = floatval(str_replace('%', '', $_ENV['backUpContributionPrcentage'])) / 100;
        $backUpCountributionPercentage = $totalWeeklyLoad * $backUpContributionPercentage;
        return ceil($backUpCountributionPercentage);
    }


    public function calculateAdjustedWeeklyLoad($totalWeeklyLoad, $backUpContributionPercentage)
    {
        $adjustedWeeklyLoad = $totalWeeklyLoad - $backUpContributionPercentage;
        return ceil($adjustedWeeklyLoad);
    }


    public function calculateDailyPvEnergyBudget($adjustedWeeklyLoad)
    {
        $dailyPvEnergyBudget = $adjustedWeeklyLoad / 7;
        return ($dailyPvEnergyBudget);
    }


    public function calculateTotalDailyAmpHours($dailyPvEnergyBudget, $systemVolt)
    {
        $totalDailyAmpHours = $dailyPvEnergyBudget / $_ENV['systemVolts'];
        return ceil($totalDailyAmpHours);
    }


    public function calculateArrayCurrentInAmps($totalDailyAmpHours, $brightSunShineHours)
    {
        $arrayCurrentInAmps = $totalDailyAmpHours / $_ENV['brightSunshineHours'];
        return ceil($arrayCurrentInAmps);
    }



    public function calculateModuleCurrentInAmps()
    {
        $calculateModuleCurrentInAmps = 300 / 36;
        return ceil($calculateModuleCurrentInAmps);
    }


    public function calculateNumberOfModuleInParallel($arrayCurrentInAmps, $calculateModuleCurrentInAmps)
    {
        $calculateNumberOfModuleInParallel = $arrayCurrentInAmps / $calculateModuleCurrentInAmps;
        return ceil($calculateNumberOfModuleInParallel);
    }

    public function calculateNumberOfModuleInSeries()
    {
        $calculateNumberOfModuleInSeries = $_ENV['systemVolts'] / $_ENV['moduleVoltage'];
        return ceil($calculateNumberOfModuleInSeries);
    }
    public function calculatetotalNumberOfPvModules($calculateNumberOfModuleInSeries, $calculateNumberOfModuleInParallel)
    {

        $calculatetotalNumberOfPvModules = $calculateNumberOfModuleInSeries *  $calculateNumberOfModuleInParallel;

        return $calculatetotalNumberOfPvModules;
    }

    public function calculateNormalStorageCapacityInAmps($totalDailyAmpHours, $daysOfAutonomy)
    {

        $calculateNormalStorageCapacityInAmps = $totalDailyAmpHours / $_ENV['daysOfAutonomy'];

        return $calculateNormalStorageCapacityInAmps;
    }

    public function calculateRequiredBateryCapacityInAmpHours($calculateNormalStorageCapacityInAmps, $daysOfAutonomy)
    {

        $maximumDrawDown = floatval(str_replace('%', '', $_ENV['maximumDrawDown'])) / 100;
        $calculateRequiredBateryCapacityInAmpHours = $calculateNormalStorageCapacityInAmps / $maximumDrawDown;

        return ceil($calculateRequiredBateryCapacityInAmpHours);
    }

    public function calculateTotalBatteryCapacityInAmpsHours($calculateRequiredBateryCapacityInAmpHours, $factorForColdWater)
    {

        $factorForColdWater = floatval(str_replace('%', '', $_ENV['factorForColdWater'])) / 100;
        $calculateTotalBatteryCapacityInAmpsHours = $calculateRequiredBateryCapacityInAmpHours / $factorForColdWater;

        return ceil($calculateTotalBatteryCapacityInAmpsHours);
    }


    public function calculateNumbersOfbatteryInParralel($calculateRequiredBateryCapacityInAmpHours, $singleBatteryCapacityInAmpHours)
    {

        $calculateNumberOfBatteryInParallel = $calculateRequiredBateryCapacityInAmpHours / $_ENV['singleBatteryCapacityInAmpHours'];

        return ceil($calculateNumberOfBatteryInParallel);
    }


    public function calculateNumbersOfbatteryInSeries()
    {


        $calculateNumbersOfbatteryInSeries = $_ENV['systemVolts'] / $_ENV['ratedBatteryVoltage'];

        return ceil($calculateNumbersOfbatteryInSeries);
    }


    public function calculateTotalNumbersOfBattery($calculateNumbersOfbatteryInSeries, $calculateNumberOfBatteryInParallel)
    {

        $calculateTotalNumbersOfbattery = $calculateNumbersOfbatteryInSeries * $calculateNumberOfBatteryInParallel;

        return ($calculateTotalNumbersOfbattery);
    }


    public function calculateChargeControllerSize($arrayCurrentInAmps)
    {

        $calculateNumbersOfControllerRequired = $arrayCurrentInAmps /  $_ENV['controllerAmp'];

        return ceil($calculateNumbersOfControllerRequired);
    }


    public function calculateRecommendedPvModule($calculatetotalNumberOfPvModules)
    {

        $hybridRatio = floatval(str_replace('%', '', $_ENV['hybridRatio'])) / 100;

        $calculateRecommendedPvModules = $calculatetotalNumberOfPvModules / $hybridRatio;

        return ($calculateRecommendedPvModules);
    }


    public function calculateTotalNumbersOfPvModules($calculatetotalNumberOfPvModules)
    {

        return ($calculatetotalNumberOfPvModules);
    }

    public function  calculateBatterySize($totalDailyLoad)
    {

        $calculateBatterySize = $totalDailyLoad / $_ENV['batteryVolatage'];

        return ceil($calculateBatterySize);
    }
}
