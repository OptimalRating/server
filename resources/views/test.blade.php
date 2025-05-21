<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>Test Document</title>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
            </head>
            <body>
                <form>
         <label>Enter phone number</label>
         <input type="text" id="number" placeholder="+91000"/>
          <br>
         <div id="recaptcha-cantainer"></div><br>
         <button type="button" onClick="sendCode()">Send code</button>

                </form>

                <form style="margin-top:100px">
                    <label for="">Enter Verification Code</label>
                    <input type="text" id="verificationCode" placeholder="Enter verification code" />
                    <button type="button" onClick="verifyCode()">Verify Code</button>
                </form>
                <div id="successMessage" style="color:green; display:none;"></div>
                <div id="error" style="color:red; display:none;"></div>
                <div id="sentMessage" style="color:green; display:none;"></div>

                <script src="https://www.gstatic.com/firebasejs/6.0.2/firebase.js"></script>

                <script>

                    var firebaseConfig={
                        apiKey: "AIzaSyChQgnEhW5dh1xEXMYUiqA4OSoxm96Qv8k",
        authDomain: "optimal-rating-429008.firebaseapp.com",
        projectId: "optimal-rating-429008",
        storageBucket: "optimal-rating-429008.appspot.com",
        messagingSenderId: "144275899434",
        appId: "1:144275899434:web:70b6e934cb2c55a951af53",
        measurementId: "G-L57EJZH41X"
                    }

                    firebase.initializeApp(firebaseConfig);
                </script>

                <script type="text/javascript">
                    window.onload = function(){
                        render();
                    }
                    function render(){
                        window.recaptchaVerifier = new firebase.auth.RecaptchaVerifier('recaptcha-cantainer')
                    recaptchaVerifier.render();
                    }

                    function sendCode(){
                      var number = $('#number').val();
                      firebase.auth().signInWithPhoneNumber(number, window.recaptchaVerifier).then(function(confirmationResult){
                        window.confirmationResult = confirmationResult;
                        coderesult = confirmationResult;

                        $('#sentMessage').text('Message Sent Successfully');
                        $('#sentMessage').show();
                      }).catch(function(error){
                        $('#error').text(error.message);
                        $('#error').show();
                      })
                    }

                    function verifyCode(){
                        var code = $('#verificationCode').val();

                        coderesult.confirm(code).then(function(result){
                            var user = result.user;

                            $('#successMessage').text('Successfully registered');
                        $('#sentMessage').show();
                      }).catch(function(error){
                        $('#error').text(error.message);
                        $('#error').show();
                      })
                    }
                </script>

            </body>
            </html>