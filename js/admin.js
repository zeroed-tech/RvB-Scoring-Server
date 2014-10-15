/**********************************/
//        USER MANAGEMENT         //
/**********************************/

/*
 Enables or disables team. Determination is made based on the buttons text

 teamId - Team to enable or disable
 enabling - true if we are enabling the team with teamId, false if disabling
 target - The button clicked. Used to re-style the button after processing the click
 teamType - determines if this team should be Red, Blue or Purple
 */
function enableTeam(teamId, enabling, target, $teamType) {
    var data = new Object();
    data.functionCall = enabling ? "enable" : "disable";
    if(enabling){
        data.teamType = $teamType;
    }
    data.teamId = teamId;
    postData("admin.php", "data=" + JSON.stringify(data), function () {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            var results = JSON.parse(xmlhttp.responseText);
            showMessage(results.message, results.result, false);
            if(target.hasClass("red")){
                target.removeClass("red");
                target.addClass("green");
                target.html("Enable Team");
                target.parent().parent().children()[2].innerHTML = "Disabled";
            }else{
                target.removeClass("green");
                target.addClass("red");
                target.html("Disable Team");
                target.parent().parent().children()[2].innerHTML = "Enabled";
            }
        }
    });
}

/*
 Promotes the specified team to an admin

 teamId - Team to promote or revoke
 revoke - If this is true then teamId will be demoted to a standard user
 target - The button clicked. Used to re-style the button after processing the click
 */
function makeAdmin(teamId, revoke, target) {
    var confirmMessage = (revoke == 0 ? "Making a team an admin will give them the same access rights as you have. Are you sure you wish to proceed?" : "Revoking a teams admin rights will prevent them from accessing the admin console. Are you sure you wish to proceed?");
    if (confirm(confirmMessage)) {
        var data = new Object();
        data.functionCall = 'makeAdmin';
        data.teamId = teamId;
        data.revoke = revoke;
        postData("admin.php", "data=" + JSON.stringify(data), function () {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                var results = JSON.parse(xmlhttp.responseText);
                showMessage(results.message, results.result, false);
                if(target.hasClass("red")){
                    target.removeClass("red");
                    target.addClass("green");
                    target.html("Promote To Admin");
                    target.parent().parent().children()[4].innerHTML = "False";
                }else{
                    target.removeClass("green");
                    target.addClass("red");
                    target.html("Revoke Admin");
                    target.parent().parent().children()[4].innerHTML = "True";
                }
            }
        });
    }
}

/*
 Deletes the team specified

 teamId - Team to delete
 target - The button clicked
 */
function deleteTeam(teamId, target) {
    if (confirm("Are you sure you want to delete this team?")) {
        var data = new Object();
        data.functionCall = 'deleteTeam';
        data.teamId = teamId;
        postData("admin.php", "data=" + JSON.stringify(data), function () {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                var results = JSON.parse(xmlhttp.responseText);
                showMessage(results.message, results.result, false);
                target.parent().parent().slideToggle('fast');
            }
        });
    }
}

/*
 Resets the password of the team specified

 teamId - Team who's password needs resetting
 */
function resetPassword(teamId) {
    var newPassword = prompt("Enter the new password for team "+teamId);
    if(newPassword != null) {
        var data = new Object();
        data.functionCall = 'resetPassword';
        data.teamId = teamId;
        data.newPassword = newPassword;
        postData("admin.php", "data=" + JSON.stringify(data), function () {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                var results = JSON.parse(xmlhttp.responseText);
                showMessage(results.message, results.result, false);
            }
        });
    }
}


/**********************************/
//     CHALLENGE MANAGEMENT       //
/**********************************/

function addChallenge(addChallenge) {
    //Build and display add challenge dialog
    $('#backPane').html(' \
	<div class="pane" onclick="event.stopPropagation();"> \
		<form action="admin.php" method="post" class="adminForms" enctype="multipart/form-data" id="challengeUploadForm"> \
			<h3>Add a new Challenge</h3> \
			<label> \
				<span>Challenge Name:</span> \
				<input id="challengeName" type="text" placeholder="Hack the Gibson" /><br /> \
			</label> \
			<label> \
				<span>Author:</span> \
				<input id="challengeAuthor" type="text" placeholder="John Smith" /><br /> \
			</label> \
			<label> \
				<span>Points Available:</span> \
				<input id="scoreValue" type="text" placeholder="200" /><br /> \
			</label> \
			<label> \
				<span>Challenge Files:</span> \
				<input name="challengeFiles" id="challengeFiles" type="file" /><br /> \
			</label> \
			<label> \
				<span>Base Image:</span> \
				<select name="baseImage" id="baseImage"> \
				</select> \
			</label> \
			<input type="submit" value="Add Challenge"> \
		</form> \
		<div id="progressbarouter"><div id="progressbarinner"></div><div id="progresstext">0%</div></div> \
	</div>');

    var data = new Object();
    data.functionCall = 'getBaseContainers';
    postData("admin.php", "data=" + JSON.stringify(data), function () {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            results = JSON.parse(xmlhttp.responseText);
            results.forEach(function (entry) {
                $('#baseImage').append('<option value="' + entry + '">' + entry + '</option>');
            });
        }
    });

    //Called when the form is submitted
    $('#challengeUploadForm').submit(function (event) {
        event.preventDefault();

        var data = new Object();
        data.functionCall = 'addChallenge';
        data.challengeName = $('#challengeName').val();
        data.challengeAuthor = $('#challengeAuthor').val();
        data.scoreValue = $('#scoreValue').val();
        data.baseImage = $('#baseImage').val()

        var options = {
            beforeSubmit: preSubmit,
            success: postSubmit,
            uploadProgress: submitProgress,
            data: {data: JSON.stringify(data)},
            resetForm: false
        };
        $(this).ajaxSubmit(options);

        /*
        //Fade the panel out and change it to the status screen
        var loadingRing = '<div class="outer"><div class="inner"><div class="loadingBar bar1"></div><div class="loadingBar bar2"></div><div class="loadingBar bar3"></div><div class="loadingBar bar4"></div><div class="loadingBar bar5"></div><div class="loadingBar bar6"></div><div class="loadingBar bar7"></div><div class="loadingBar bar8"></div></div></div>'
        $('.pane').html('<ul> \
			<li>Cloning base container'+loadingRing+'<img class="stateImg" src="" /></li> \
			<hr /> \
			<li>Starting container'+loadingRing+'<img class="stateImg" src="" /></li> \
			<hr /> \
			<li>Uploading files to container'+loadingRing+'<img class="stateImg" src="" /></li> \
			<hr /> \
			<li>Configuring container using uploaded tarball'+loadingRing+'<img class="stateImg" src="" /></li> \
			<hr /> \
			<li>Saving challenge to database'+loadingRing+'<img class="stateImg" src="" /></li> \
			</ul>');

        $('.outer').hide();
        $('.pane ul li:nth-child(1) div').fadeIn("slow");
        $('.pane').fadeIn("fast");
        */
        hidePopup();

    });
    showPane();
}


/**********************************/
//    CHALLENGE CONFIGURATION     //
/**********************************/


/*
 This function is the equivilant to submitting a form.
 The primary difference is this concatinates the header fields with their respective styles before storing in the database
 */
function updateGameRules() {
    var data = new Object();
    data.functionCall = 'updateConfig';
    data.startTime = $("#startTime").val();
    data.endTime = $("#endTime").val();
    data.motd = $("#motd_edit").val();
    data.leftHeader = $("#leftHeaderSet").val() + "<><>" + $("#leftHeaderStyle").val();
    data.centerHeader = $("#centerHeaderSet").val() + "<><>" + $("#centerHeaderStyle").val();
    data.rightHeader = $("#rightHeaderSet").val() + "<><>" + $("#rightHeaderStyle").val();

    postData("admin.php", "data=" + JSON.stringify(data), function () {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            var results = JSON.parse(xmlhttp.responseText);
            showMessage(results.message, results.result, true);
        }
    });
}

/*
 Sets all style fields back to their default values
 */
function resetStyles() {
    $("#leftHeaderStyle").val("text-align: center;	margin: 200px auto; font-family: \"Museo\"; font-size: 100px; text-transform: uppercase;color: #fff;text-shadow: 0 0 10px #000, 0 0 20px #000, 0 0 30px #000, 0 0 40px #ff0000, 0 0 70px #ff0000, 0 0 80px #ff0000, 0 0 100px #ff0000, 0 0 150px #ff0000;");
    $("#centerHeaderStyle").val("padding-left: 25px; padding-right: 25px;text-align: center;margin: 100px auto;font-family: \"League-Gothic\", Courier;font-size: 50px; text-transform: uppercase;color: #fff;text-shadow: 0 0 10px #fff, 0 0 20px #fff, 0 0 30px #fff, 0 0 40px #000, 0 0 70px #000, 0 0 80px #000, 0 0 100px #000, 0 0 150px #000;");
    $("#rightHeaderStyle").val("text-align: center;margin: 200px auto;font-family: \"Museo\";font-size: 100px; text-transform: uppercase;color: #fff;text-shadow: 0 0 10px #fff, 0 0 20px #fff, 0 0 30px #fff, 0 0 40px #0000ff, 0 0 70px #0000ff, 0 0 80px #0000ff, 0 0 100px #0000ff, 0 0 150px #0000ff;");
}

/*
 Setup listeners
 */
$(document).ready(function () {

    $("#sendBroadcast").click(function(event){
        var data = new Object();
        data.functionCall = 'broadcastMessage';
        data.message = $("#broadcastMessage").val();
        postData("admin.php", "data=" + JSON.stringify(data), function () {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                var results = JSON.parse(xmlhttp.responseText);
                showMessage(results.message, results.result, false);
            }
        });
    });

    /**
     * User Management
     **/
    $(".enableTeam").click(function (event) {
        var id = $(this).parent().parent().children()[0].innerHTML;
        var clickedButton = $(this);
        if(clickedButton.hasClass("red")){
            enableTeam(id, false, clickedButton);
        }else{
            //Ask the admin which team this user should be put on
            $('#backPane').html(' \
            <div class="pane" onclick="event.stopPropagation();"> \
                <h3>Enable team</h3> \
                <p>What type of team is this?:<br /><br />\
                    <b>Red</b> - The attacking team. The goal of the red team is to locate targets, find vulnerabilities, develop exploits and capture flags.<br /><br />\
                    <b>Blue</b> - The defending team. The goal of the blue team is to identify holes in the software running on their hosts and develop patches before the attackers can exploit them.<br /><br />\
                    <b>Purple</b> - Purple teams take on the role of both attacker and defender at the same time. This requires much more skill and time management.<br /><br />\
                </p>\
                <select id="teamType">\
                    <option value="1">Red</option> \
                    <option value="2">Blue</option>\
                    <option value="3">Purple</option>\
                </select>\
                <input id="enableTeamSubmit" type="submit" value="Enable Team"> \
            </div>');
            $("#enableTeamSubmit").click(function(event){
                enableTeam(id, true, clickedButton, $("#teamType").val());
                hidePopup();
            });
            showPane();
        }

    });

    $(".makeAdmin").click(function (event) {
        var id = $(this).parent().parent().children()[0].innerHTML;
        var revoke = false;
        if($(this).hasClass("red")){
            revoke = true;
        }else{
            revoke = false;
        }
        makeAdmin(id, revoke, $(this));
    });

    $(".deleteTeam").click(function (event) {
        var id = $(this).parent().parent().children()[0].innerHTML;
        deleteTeam(id, $(this));
    });

    $(".resetPassword").click(function (event) {
        var id = $(this).parent().parent().children()[0].innerHTML;
        resetPassword(id);
    });

    /**
     * Challenge Management
     */
    $(".enable").click(function (event) {
        var id = $(this).parent().attr("id").split('_')[1];
        $(this).parent().parent().removeClass("disabled");
        $(this).parent().parent().addClass("enabled");
        var data = new Object();
        data.functionCall = 'enableChallenge';
        data.challengeId = id;
        postData("admin.php", "data=" + JSON.stringify(data), function () {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                results = JSON.parse(xmlhttp.responseText);
                showMessage(results.message, results.result, false);
            }
        });
    });

    $(".disable").click(function (event) {
        var id = $(this).parent().attr("id").split('_')[1];
        $(this).parent().parent().removeClass("enabled");
        $(this).parent().parent().addClass("disabled");
        var data = new Object();
        data.functionCall = 'disableChallenge';
        data.challengeId = id;
        postData("admin.php", "data=" + JSON.stringify(data), function () {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                var results = JSON.parse(xmlhttp.responseText);
                showMessage(results.message, results.result, false);
            }
        });
    });

    //Displays a pre-filled form with the current challenge details. On submit these details are used to update the challenge in the database
    $(".edit").click(function (event) {
        var clickedChallenge = $(this).parent();
        var id = clickedChallenge.attr("id").split('_')[1];

        var data = new Object();
        data.functionCall = 'editChallenge';
        data.challengeId = id;
        postData("admin.php", "data=" + JSON.stringify(data), function () {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                var results = JSON.parse(xmlhttp.responseText);
                $('#backPane').html(' \
                <div class="pane" onclick="event.stopPropagation();"> \
                    <form action="admin.php" method="post" class="adminForms" id="challengeEditForm"> \
                        <h3>Edit ' + results.challengeName + '</h3> \
			            <label> \
                            <span>Author:</span> \
                            <input id="challengeAuthor" type="text" placeholder="John Smith" value="' + results.challengeAuthor + '" /><br /> \
                        </label> \
                        <label> \
                            <span>Points Available:</span> \
                            <input id="scoreValue" type="text" placeholder="200" value="' + results.scoreValue + '" /><br /> \
                        </label> \
                        <input type="submit" value="Save Challenge"> \
                    </form> \
                </div>');

                //Add a listener to the form that is called when the form is submitted
                $('#challengeEditForm').submit(function (event) {
                    event.preventDefault();

                    var data = new Object();
                    data.functionCall = 'editChallenge';
                    data.challengeAuthor = $('#challengeAuthor').val();
                    data.scoreValue = $('#scoreValue').val();
                    data.challengeId = id;

                    postData("admin.php", "data=" + JSON.stringify(data), function () {
                        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                            var results = JSON.parse(xmlhttp.responseText);
                            showMessage(results.message, results.result, false);
                            if (results.result) {
                                clickedChallenge.find(".author").text("Author: " + data.challengeAuthor);

                                hidePopup();
                            }
                        }
                    });

                });
                showPane();
            }
        });
    });

    $(".delete").click(function (event) {
        var id = $(this).parent().attr("id").split('_')[1];
        if (confirm("This will delete all instances of this this challenge including the template.\n\n Are you sure you wish to continue?")) {
            $(this).parent().parent().hide();
            var data = new Object();
            data.functionCall = 'deleteChallenge';
            data.challengeId = id;
            postData("admin.php", "data=" + JSON.stringify(data), function () {
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                    var results = JSON.parse(xmlhttp.responseText);
                    showMessage(results.message, results.result, false);
                }
            });
        }
    });

    /**
     * Container Management
     */
    $(".startContainer").click(function (event) {
        var data = new Object();
        data.functionCall = 'startContainer';
        data.containerName = $(this).parent().parent().children()[0].innerHTML;
        $(this).parent().parent().children("td:nth-child(2)").html("RUNNING");
        postData("admin.php", "data=" + JSON.stringify(data), function () {
        });
    });

    $(".stopContainer").click(function (event) {
        var data = new Object();
        data.functionCall = 'stopContainer';
        data.containerName = $(this).parent().parent().children()[0].innerHTML;
        $(this).parent().parent().children("td:nth-child(2)").html("STOPPED");
        postData("admin.php", "data=" + JSON.stringify(data), function () {
        });
    });

    $(".deleteContainer").click(function (event) {
        if (confirm("WARNING\n\nDeleting this container will NOT remove it from the database, only the OS. This should only be done in the event that the database encountered an error while deleting a container causing it to no longer show up in challenge management.\n\n Are you sure your wish to delete this container?")) {
            var data = new Object();
            data.functionCall = 'deleteContainer';
            data.containerName = $(this).parent().parent().children()[0].innerHTML;
            $(this).parent().parent().slideToggle('fast');
            postData("admin.php", "data=" + JSON.stringify(data), function () {
            });
        }
    });

    $(".regenFlag").click(function (event) {
        var data = new Object();
        data.functionCall = 'regenFlag';
        data.containerName = $(this).parent().parent().children()[0].innerHTML;
        postData("admin.php", "data=" + JSON.stringify(data), function () {
        });
    });


});

/**********************************/
//     File Upload Functions      //
/**********************************/

/*
 Verify that the files that are being uploaded meet the required specifications
 */
function preSubmit() {
    if ($("#challengeName").val().length < 1) {
        showMessage("Please enter a challenge name", false, false);
        return false;
    }
    if ($("#challengeAuthor").val().length < 1) {
        showMessage("You went to all that effort to make a challenge and not you don't want to take credif for it? Enter an author<br /> (this may be a good time to come up with a hacker alias)", false, false);
        return false;
    }
    if ($("#scoreValue").val().length < 1 || $("#scoreValue").val() == "0") {
        showMessage("It takes a truly heartless person to make a challenge worth no points. At Least throw them a point for solving your challenge", false, false);
        return false;
    }
    if ($("#challengeFiles").val().length < 1) {
        showMessage("A prerequisite of making a challenge is that you actually have a challenge. Select your challenge tar ball", false, false);
        return false;
    }
    //Check that the browser supports everything we need
    if (window.File && window.FileReader && window.FileList && window.Blob) {
        //Get the file size and type
        var fsize = $('#challengeFiles')[0].files[0].size;
        var ftype = $('#challengeFiles')[0].files[0].type;
        //Check that the filetype is valid. Currently only tar and gzip tar are supported. More may be added later
        switch (ftype) {
            case 'application/x-gzip':
            case 'application/x-tar':
            case 'application/zip':
            case 'application/x-bzip2':
            case ''://http file API doesnt appear to recognise 7zip files so we have to accept blank
                break;
            default:
                console.log("File Type: " + ftype);
                showMessage("<b>" + ftype + "</b> Unsupported file type! Please upload a tar or tar.gz file", false, false);
                return false
        }
    } else {
        showMessage("Your browser doesn't appear to support the features required for file upload. Please upgrade to a newer browser", false, false);
        return false
    }
}

/*
 Updates the progress bar
 */
function submitProgress(event, position, total, percentComplete) {
    //Progress bar
    $('#progressbarinner').width(percentComplete + '%');
    $('#progresstext').html(percentComplete + '%'); //update status text
    if (percentComplete > 50) {
        $('#progresstext').css('color', '#000'); //change status text to white after 50%
    }
}

function postSubmit(responseText, status) {
    var results = JSON.parse(responseText);

    showMessage(results.message, results.result, false);
}











