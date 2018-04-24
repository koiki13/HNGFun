<?php 
		
		if(!defined('DB_USER')){
			require "../../config.php";		
			try {
			    $conn = new PDO("mysql:host=". DB_HOST. ";dbname=". DB_DATABASE , DB_USER, DB_PASSWORD);
			} catch (PDOException $pe) {
			    die("Could not connect to the database " . DB_DATABASE . ": " . $pe->getMessage());
			}
		}

    try {
        $q = 'SELECT * FROM secret_word';
        $sql = $conn->query($q);
        $sql->setFetchMode(PDO::FETCH_ASSOC);
        $data = $sql->fetch();
        $secret_word = $data["secret_word"];
    } catch (PDOException $err) {

        throw $err;
    }?>
	
<?php

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		
		require "../answers.php";

		date_default_timezone_set("Africa/Lagos");

		// header('Content-Type: application/json');

		if(!isset($_POST['question'])){
			echo json_encode([
				'status' => 1,
				'answer' => "What is your question"
			]);
			return;
		}

		$question = $_POST['question']; //get the entry into the chatbot text field

		//check if in training mode
		$index_of_train = stripos($question, "train:");
		if($index_of_train === false){//then in question mode
			$question = preg_replace('([\s]+)', ' ', trim($question)); //remove extra white space from question
			$question = preg_replace("([?.])", "", $question); //remove ? and .

			//check if answer already exists in database
			$question = "%$question%";
			$sql = "select * from chatbot where question like :question";
			$stmt = $conn->prepare($sql);
			$stmt->bindParam(':question', $question);
			$stmt->execute();

			$stmt->setFetchMode(PDO::FETCH_ASSOC);
			$rows = $stmt->fetchAll();
			if(count($rows)>0){
				$index = rand(0, count($rows)-1);
				$row = $rows[$index];
				$answer = $row['answer'];	

				//check if the answer is to call a function
				$index_of_parentheses = stripos($answer, "((");
				if($index_of_parentheses === false){ //then the answer is not to call a function
					echo json_encode([
						'status' => 1,
						'answer' => $answer
					]);
				}else{//otherwise call a function. but get the function name first
					$index_of_parentheses_closing = stripos($answer, "))");
					if($index_of_parentheses_closing !== false){
						$function_name = substr($answer, $index_of_parentheses+2, $index_of_parentheses_closing-$index_of_parentheses-2);
						$function_name = trim($function_name);
						if(stripos($function_name, ' ') !== false){ //if method name contains spaces, do not invoke method
							echo json_encode([
								'status' => 0,
								'answer' => "No white spaces allowed in function name"
							]);
							return;
						}
						if(!function_exists($function_name)){
							echo json_encode([
								'status' => 0,
								'answer' => "Function not found"
							]);
						}else{
							echo json_encode([
								'status' => 1,
								'answer' => str_replace("(($function_name))", $function_name(), $answer)
							]);
						}
						return;
					}
				}
			}else{
				echo json_encode([
					'status' => 0,
					'answer' => "train: question # answer # password</b>"
				]);
			}		
			return;
		}else{
			$question_and_answer_string = substr($question, 6);
			$question_and_answer_string = preg_replace('([\s]+)', ' ', trim($question_and_answer_string));
			
			$question_and_answer_string = preg_replace("([?.])", "", $question_and_answer_string);
			$split_string = explode("#", $question_and_answer_string);
			if(count($split_string) == 1){
				echo json_encode([
					'status' => 0,
					'answer' => "Wrong!"
				]);
				return;
			}
			$que = trim($split_string[0]);
			$ans = trim($split_string[1]);

			if(count($split_string) < 3){
				echo json_encode([
					'status' => 0,
					'answer' => "Enter the training password"
				]);
				return;
			}

			$password = trim($split_string[2]);
			//verify if training password is correct
			define('TRAINING_PASSWORD', 'password');
			if($password !== TRAINING_PASSWORD){
				echo json_encode([
					'status' => 0,
					'answer' => "Sorry you will not train me."
				]);
				return;
			}

			//insert into database
			$sql = "insert into chatbot (question, answer) values (:question, :answer)";
			$stmt = $conn->prepare($sql);
			$stmt->bindParam(':question', $que);
			$stmt->bindParam(':answer', $ans);
			$stmt->execute();
			$stmt->setFetchMode(PDO::FETCH_ASSOC);
			echo json_encode([
				'status' => 1,
				'answer' => "I have been trained"
			]);
			return;
		}

		echo json_encode([
			'status' => 0,
			'answer' => "Please train me"
		]);
		
	}
	else{
?>

<!DOCTYPE html>
<html lang="en-us" style="height:100%;">
  <head>
    <title>Dennis Otugo</title>
    <meta charset="UTF-8">
      
      <meta http-equiv="x-ua-compatible" content="IE=edge"/>
      <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
      <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
      <meta name="apple-mobile-web-app-title" content="Oracle JET">
      <script src="https://www.oracle.com/webfolder/technetwork/jet/js/loadStyleSheets.min.js"></script>
      <link rel="stylesheet" href="https://www.oracle.com/webfolder/technetwork/jet/css/samples/cookbook/demo.css">
      <script>
        // The "oj_whenReady" global variable enables a strategy that the busy context whenReady,
        // will implicitly add a busy state, until the application calls applicationBootstrapComplete
        // on the busy state context.
        window["oj_whenReady"] = true;
    </script>
    <script src="https://static.oracle.com/cdn/jet/v5.0.0/3rdparty/require/require.js"></script>
    <!-- RequireJS bootstrap file -->
    <script src="https://www.oracle.com/webfolder/technetwork/jet/js/spaWork.min.js"></script>
    <!--customHeaderStart-->
    
    <!--customHeaderEnd-->
  </head>

  <body class="demo-disable-bg-image">
        <div id="demo-container">
          <div id="avatar-container" class="demo-flex-display">
            <div class="oj-flex">
              <div class="oj-flex oj-sm-align-items-center oj-sm-margin-2x">
                <div class="of-flex-item">
                  <oj-avatar role="img" size="[[avatarSize]]" initials='[[initials]]'
                    data-bind="attr:{'aria-label':'Avatar of ' + firstName + ' ' + lastName}">
                  </oj-avatar>
                </div>
              </div>
            </div>
          </div>
        </div>

<script>
require(['ojs/ojcore', 'knockout', 'jquery', 'ojs/ojknockout', 'ojs/ojcomposite',
 'ojs/ojbutton','ojs/ojavatar','ojs/ojvalidation','ojs/ojlabel'],
function(oj, ko, $) {
  function model() {
    var self = this;
    self.firstName = 'Dennis';
    self.lastName = 'Otugo';
    self.initials = oj.IntlConverterUtils.getInitials(self.firstName,self.lastName);
    self.avatarSize = ko.observable("md");
    self.sizeOptions = ko.observableArray(['xxs', 'xs','sm','md','lg','xl','xxl']);
  }

  $(function() {
      ko.applyBindings(new model(), document.getElementById('demo-container'));
  });

});
</script>

      </div>
    </div>

	<div class="col-md-4 offset-md-1 chat-frame">
			<h2 class="text-center"><u>CHATBOT</u></h2>
			<div class="row chat-messages" id="chat-messages">
				<div class="col-md-12" id="message-frame">
					<div class="row single-message">
						<div class="col-md-12 single-message-bg">
							<h5>Hello <span style="font-weight: bold">iam__bot</span></h5>
						</div>
					</div>
					<div class="row single-message">
						<div class="col-md-12 single-message-bg">
							<h5>Ask me your questions </h5>
						</div>
					</div>
					<div class="row single-message">
						<div class="col-md-12 single-message-bg">
							
							<h5>To train me, type <br/><b>train: question # answer # password</b><h5>
						</div>
					</div>
				</div>
			</div>
			
			
			<div class="row" style="margin-top: 40px;">
				<form class="form-inline col-md-12 col-sm-12" id="question-form">
					<div class="col-md-12 col-sm-12 col-12">
						<input class="form-control w-100" type="text" name="question" placeholder="Enter your message" />
					</div>
					<div class="col-md-12 col-sm-12 col-12" style="margin-top: 20px">
						<button type="submit" class="btn btn-info float-right w-100" >Enter</button>
					</div>
				</form>	
			</div>
		</div>
	</div>
</div>

	
	$(document).ready(function(){
		var questionForm = $('#question-form');
		questionForm.submit(function(e){
			e.preventDefault();
			var questionBox = $('input[name=question]');
			var question = questionBox.val();
			
			//display question in the message frame as a chat entry
			var messageFrame = $('#message-frame');
			var chatToBeDisplayed = '<div class="row single-message">'+
						'<div class="col-md-12 offset-md-2 single-message-bg2">'+
							'<h5>'+question+'</h5>'+
						'</div>'+
					'</div>';
			

			messageFrame.html(messageFrame.html()+chatToBeDisplayed);
			$("#chat-messages").scrollTop($("#chat-messages")[0].scrollHeight);

			//send question to server
			$.ajax({
				url: "/profiles/dennisotugo.php",
				type: "post",
				data: {question: question},
				dataType: "json",
				success: function(response){
					if(response.status == 1){
						var chatToBeDisplayed = '<div class="row single-message">'+
									'<div class="col-md-12 single-message-bg">'+
										'<h5>'+response.answer+'</h5>'+
									'</div>'+
								'</div>';

						messageFrame.html(messageFrame.html()+chatToBeDisplayed);
						questionBox.val("");	
						$("#chat-messages").scrollTop($("#chat-messages")[0].scrollHeight);
					}else if(response.status == 0){
						var chatToBeDisplayed = '<div class="row single-message">'+
									'<div class="col-md-12 single-message-bg">'+
										'<h5>'+response.answer+'</h5>'+
									'</div>'+
								'</div>';

						messageFrame.html(messageFrame.html()+chatToBeDisplayed);
						$("#chat-messages").scrollTop($("#chat-messages")[0].scrollHeight);
					}
				},
				error: function(error){
					console.log(error);
				}
			})

		});
	});
</script>	
</body>
</html>
<?php } ?>
