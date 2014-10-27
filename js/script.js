//Global variable so all functions can access xmlhttp
var xmlhttp;
function postData(url, data, callback, fileProgressCallback){
	if (window.XMLHttpRequest){
	  xmlhttp=new XMLHttpRequest();
	}else{
		alert("I refuse to run a CTF for people that use IE6")
		return;
	}
	//May user later for larger file uploads
	if(typeof fileProgressCallback != 'undefined')
		xmlhttp.onprogress=fileProgressCallback;
	xmlhttp.onreadystatechange=callback;
	xmlhttp.open("POST",url, true);
	xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
	xmlhttp.send(data);
}

//Make back pane opaque
function showPane(){
	//document.getElementById("backPane").style.display = "block";
    $("#backPane").fadeIn("fast");
}

//Make back pane invisible
function hidePopup(){
	//document.getElementById("backPane").style.display = "none";
	//document.getElementById("backPane").innerHTML = "";
    $("#backPane").fadeOut("fast");
    $("#backPane").html("");
}

function register(){
	document.getElementById("backPane").innerHTML = ' \
	<div class="pane" id="registerPane" onclick="event.stopPropagation();"> \
		<h3>Team Registration</h3> \
		<form action="accounts.php" method="post" id="paneForm"> \
			<label> \
				<span>Team Name:</span> \
				<input id="teamName" type="text" placeholder="l33t Hax0rs" /><br /> \
			</label> \
			<label> \
				<span>Password:</span> \
				<input id="teamPassword" type="password" placeholder="abc123" /><br /> \
			</label> \
			<input type="submit" value="Register" /><br /> \
		</form> \
	</div>';

	//Setup a form submit listener
	$('#paneForm').submit(function (event) { 
		event.preventDefault();

		var data = new Object();
		data.functionCall = 'register';
		data.teamName = $("#teamName").val();
		data.teamPassword = $("#teamPassword").val();

		var options = {
					success: postSubmit,
					data: {data: JSON.stringify(data)},
					resetForm: false};
	    $(this).ajaxSubmit(options);
        hidePopup();
	});

	showPane();
}

//Build login form and display on the backpane
function login(){

		document.getElementById("backPane").innerHTML = ' \
		<div class="pane" id="loginPane" onclick="event.stopPropagation();"> \
			<h3>Login</h3> \
			<form action="accounts.php" method="post" id="paneForm"> \
				<span id="reloadOnSubmit"></span> \
				<label> \
				<span>Team Name:</span> \
					<input id="teamName" type="text" placeholder="l33t Hax0rs" /><br /> \
				</label> \
				<label> \
				<span>Password:</span> \
					<input id="teamPassword" type="password" placeholder="abc123" /><br /> \
				</label> \
				<input type="submit" value="Login" /><br /> \
			</form> \
		</div>';


		$('#paneForm').submit(function (event) { 
			event.preventDefault();

			var data = new Object();
			data.functionCall = 'login';
			data.teamName = $("#teamName").val();
			data.teamPassword = $("#teamPassword").val();
            $("#teamPassword").val('');

			var options = {
						success: postSubmit,
						data: {data: JSON.stringify(data)},
						resetForm: false};
		    $(this).ajaxSubmit(options);            
		});

		showPane();
    $("#teamName").focus();
}

function logout(){
	var data = new Object();
	data.functionCall = 'logout';
	postData("accounts.php","data="+JSON.stringify(data), function(){
		if (xmlhttp.readyState==4 && xmlhttp.status==200){
			location.reload();
	    }
		});
}

function rules(){
	document.getElementById("backPane").innerHTML = ' \
	<div class="pane" onclick="event.stopPropagation();"> \
        <ul id="rulesList"></ul>\
    </div>';
    var data = {functionCall: 'getRules'};

    postData("management.php","data="+JSON.stringify(data), function(){
        if (xmlhttp.readyState==4 && xmlhttp.status==200){
            var rules = JSON.parse(xmlhttp.responseText);

            for(var rule in rules){
                $("#rulesList").append("<li>"+rules[rule].rule+"</li><li></li>")
            }
        }
    });
		showPane();
}

//Retrieve current scoreboard from the server then display
function scoreboard(){
	document.getElementById("backPane").innerHTML = ' \
	<div class="pane" onclick="event.stopPropagation();"> \
	<table id="scoreTable">\
		<tr><th>Position</th><th>Team Name</th><th>Score</th></tr>\
	</table>\
	</div>';
	var data = new Object();
	data.functionCall = 'getScoreboard';
	
	postData("management.php","data="+JSON.stringify(data), function(){	
		if (xmlhttp.readyState==4 && xmlhttp.status==200){
			var scores = JSON.parse(xmlhttp.responseText);
			for(var score in scores){
				$("#scoreTable").append("<tr class='"+(scores[score].currentTeam ? "myTeam":"someTeam")+"'><td>"+(parseInt(score)+1)+"</td><td>"+scores[score].teamName+"</td><td>"+scores[score].currentScore+"</td></tr>")
			}

		}
	});

	showPane();
}

function submitFlag(){
		document.getElementById("backPane").innerHTML = ' \
		<div class="pane" id="flagSubmit" onclick="event.stopPropagation();"> \
			<h3>Submit Flag</h3> \
			<form action="management.php" method="post" id="paneForm"> \
				<input id="flag" type="text" placeholder="Flag, eg FLAG{THIS IS A FLAG}" onkeypress="if(event.keyCode==13){submitFlag(1);}" /><br /> \
				<input type="submit" value="Submit" /><br /> \
			</form> \
		</div>';

		$('#paneForm').submit(function (event) { 
			event.preventDefault();

			var data = new Object();
			data.functionCall = 'submitFlag';
			data.flag = $("#flag").val();

			var options = {
						success: postSubmit,
						data: {data: JSON.stringify(data)},
						resetForm: false};
		    $(this).ajaxSubmit(options);
            hidePopup();
		});

		showPane();
}

/*
Called after jquery forms has finished submitting a form and received a response from the server
*/
function postSubmit(responseText, statusText, xhr, form){
    console.log(responseText);
	var results = JSON.parse(responseText);
	showMessage(results.message, results.result, ($("#reloadOnSubmit").length ? true:false));
}

/*
Displays a toast at the top of the screen. success determines whether a success of fail toast is shown. 
If reloadOnSuccess is true true then the page will reload without displaying a toast
*/
function showMessage(message, success, reloadOnSuccess){
	//Setup toastr options
    toastr.options = {
        "closeButton": false,
        "debug": false,
        "positionClass": "toast-top-right",
        "onclick": null,
        "showDuration": "10000",
        "hideDuration": "10000",
        "timeOut": "10000",
        "extendedTimeOut": "10000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };
	if(success){
		if(reloadOnSuccess){
			location.reload();
		}
		toastr.success(message);
	}else{
		toastr.error(message);
	}
}

/*
Run on startup. Used to configure listeners
 */
$(document).ready(function() {

    var hash = window.location.hash.replace('#','');
    if(hash != ""){
        $("#"+hash).children(".sectionContent").slideToggle(0);
    }

    $(".sectionHeader").click(function (event) {
        window.location.hash = $(this).parent().attr("id");
        if ($(this).parent().children(".sectionContent").is(":visible")) {
            $(this).parent().children(".sectionContent").slideToggle('slow');
            window.location.hash="";
            return;
        }
        $(".sectionHeader").each(function () {
            if ($(this).parent().children(".sectionContent").is(":visible")) {
                $(this).parent().children(".sectionContent").slideToggle('slow');
            }
        });
        $(this).parent().children(".sectionContent").slideToggle('slow');
    });

    $( ".restart" ).click(function (event){
        var id = $(this).parent().attr("id").split('_')[1];
        var data = new Object();
        data.functionCall = 'restart';
        data.challengeId = id;
        postData("manage.php","data="+JSON.stringify(data), function(){
            if (xmlhttp.readyState==4 && xmlhttp.status==200){
                var results = JSON.parse(xmlhttp.responseText);
                showMessage(results.message, results.result, false);
            }
        });
    });

    $( ".revert" ).click(function (event){
        if (confirm("This will revert this container back to its original state.\n\nAre you sure you wish to continue?")) {
            var id = $(this).parent().attr("id").split('_')[1];
            var data = new Object();
            data.functionCall = 'revert';
            data.challengeId = id;
            postData("manage.php", "data=" + JSON.stringify(data), function () {
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                    var results = JSON.parse(xmlhttp.responseText);
                    showMessage(results.message, results.result, false);
                }
            });
        }
    });

    /*********************************
     *                               *
     *  Real time messaging service  *
     *                               *
     ********************************/



    var eventSource = null;
    var messagesShown = [];

    window.setInterval(function(){
        if(eventSource == null) {
            eventSource = new EventSource("rtms.php");
        }else{
            return;
        }

        eventSource.onmessage = function (e) {
            //console.log(e);
        };

        eventSource.addEventListener('message', function (e) {
            toastr.options = {
                "closeButton": false,
                "debug": false,
                "positionClass": "toast-top-right",
                "onclick": null,
                "showDuration": "10000",
                "hideDuration": "10000",
                "timeOut": "10000",
                "extendedTimeOut": "10000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            };
            var data = JSON.parse(e.data);
            switch (data.type) {
                case "message":
                    if(data.id != -1) {
                        if ($.inArray(data.id, messagesShown) != -1) {
                            break;
                        }
                    }
                    messagesShown.push(data.id);
                    toastr.info(data.message);
                    break;
                case "flagCaptured":

                    break;
                case "timeWarning":

                    break;
                default:

                    break;
            }
        }, false);

        //Server closed the connection
        eventSource.onerror = function (e) {
            eventSource.close();
            eventSource = null;
        };
    }, 5000);



});