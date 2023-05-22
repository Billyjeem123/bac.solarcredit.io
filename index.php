<!DOCTYPE html>
<html>
<head>
  <title>Login Form</title>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    $(document).ready(function(){
      $('#loginForm').submit(function(event){
        event.preventDefault(); //prevent default form submission
        var mail = $('#mail').val();
        var pword = $('#pword').val();
        var apptoken = '0000'; // Replace with your app token
        $.ajax({
          url: "https://bac.solarcredit.io/v0.1/api/login",
          type: "POST",
          headers: {
            "Authorization": "Bearer 6455ef91d108f",
            "Content-Type" : "application/json"
          },
          data: JSON.stringify({
            mail: mail,
            pword: pword,
            apptoken: apptoken
          }),
          success: function(response){
            // handle successful login response
            alert(response);
          }
        });
      });
    });
  </script>

</head>
<body>
  <form id="loginForm">
    <label for="email">Email:</label>
    <input type="text" id="mail" name="mail"><br><br>
    <label for="password">Password:</label>
    <input type="password" id="pword" name="pword"><br><br>
    <button type="submit">Submit</button>
  </form>
</body>
</html>
