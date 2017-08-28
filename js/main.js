$(document).ready(function () {
	//Changes pages via AJAX call using data-page attribute
    $("#navigation-menu button").click(function () {
        $(this).siblings("button").removeClass("active");
        $(this).addClass("active");
        var page = $(this).data("page");
        $.ajax({
            method: "POST",
            url: "index.php",
            data: {
                page: page
            },
            success: function (value) {
                $("#content").html(value);
                //Reinitialises functions
                initialise();
                forms();
            }
        });
    });
	
	//Initialises functions
    initialise();
    forms();
});

//Function initialises other functions whenever page is loaded and AJAX function call is made
//Initialise contains general functions for use throughout the project
function initialise() {
		//Set animation speed here
		var animationSpeed = 600;
		var baseHeight = $("#content").height();
	
	//Table expand/collapse functionality
		$(".expand").css({
			"padding": "0em"
		});
	
		$(".expand").click(function () {
			expandCollapseTableRow($(this));
		});
		$(".expandAll").click(function () {
			$(".expand").each(function () {
				$this = $(this);
				$el = $(".slideDown[data-id='" + $this.data("id") + "']").children("td");
				if($el.css("display") == "none") expandTable($this, $el);
			});
		});
		$(".collapseAll").click(function () {
			$(".expand").each(function () {
				$this = $(this);
				$el = $(".slideDown[data-id='" + $this.data("id") + "']").children("td");
				collapseTable($this, $el);
			});
		});
	
		function expandCollapseTableRow($this)
		{
			$el = $(".slideDown[data-id='" + $this.data("id") + "']").children("td");
			if ($el.css("display") == "none") {
				expandTable($this, $el);
			} else {
				collapseTable($this, $el);
			}
		}
		function expandTable($this, $el)
		{
			$el.stop().css({
				"padding": 0
			}).show().animate({
				"padding": "0.5em"
			}, animationSpeed);
			$el.children("div").stop().slideDown({
				duration: animationSpeed,
				complete: function () {
					baseHeight = $("#content").height();
				}
			});
			$this.find("span").html("Collapse");
		}
		function collapseTable($this, $el)
		{
			$el.children("div").stop().slideUp({
				duration: animationSpeed,
				complete: function () {
					baseHeight = $("#content").height();
				}
			});
			$el.stop().animate({
				"padding": 0
			}, {
				duration: animationSpeed,
				complete: function () {
					$(this).hide();
				}
			});
			$this.find("span").html("Expand");
		}
	//Table expand/collapse functionality ends here




	//Sets datepicker parameters
		$(".datepicker").datetimepicker({
			showAnim: "drop",
			dateFormat: "dd/mm/yy",
			showOtherMonths:true,
			selectOtherMonths:true,
		});

	//Flies flyover content div over content div and expands/collapses the content div accordingly
		$(".openFlyover").click(function () {
			var $flyover = $(".flyover[data-id='" + $(this).data("id") + "']");

			var $clone = $flyover.clone().appendTo("#content");
			$clone.css({
				"position": "relative",
				"display": "block"
			});
			fixFormCSS();
			var h = $clone.height();
			$clone.remove();

			if(h>$("#content").height())
			{
				$("#content").animate({
				  "height": h + "px"
				},
				animationSpeed,
				function () {
					$flyover.show("slide", {
						direction: "left"
					},
					animationSpeed,
					function(){
						fixFormCSS();
					});
				});
			}
			else
			{
				$flyover.show("slide", {
					direction: "left"
				},
				animationSpeed,
				function(){
					fixFormCSS();
				});
			}
			$flyover.css({'height':'auto'});
		});
		$("#content").css({height:"auto"});
		
		//Closes flyover
		$(".closeFlyover").click(function () {
			var reload = $(this).attr("data-reload");
			//Closes flyover
			$(".flyover[data-id='" + $(this).data("id") + "']").hide("slide", {
				direction: "left"
			});
			//Resets the height of #content
			if (typeof (baseHeight) != "undefined" && baseHeight !== null) {
				$("#content").animate({
						"height": baseHeight + "px"
					},
					animationSpeed,

					function () {
						$("#content").css({
							"height": "auto"
						});
						if(typeof reload == typeof undefined && reload == true)
						{
							location.reload();
						}
					}
				);
			}
		});
	//End flyover functionality
	
	//Toggles info for assignments (student side)
		$(".toggleInfo").click(function(){
			$(this).siblings(".taskInformation").slideToggle();
		});
		
	//Changes selected tab in student section (i.e. from pdf iframe to Transcript 1 in the Review task)
		$(".subnav span").click(function() {
			$(this).siblings("span").removeClass("active");
			$(this).addClass("active");
			$(".contextContainer").children().removeClass("active");
			$(".contextContainer").find("[data-tab="+$(this).data("tab")+"]").addClass("active");
		});
	//Changes the view for the student from stacked to side by side or vice versa
		$(".changeView").click(function(){
			$(this).siblings(".viewport").toggleClass("stacked").toggleClass("sideBySide");
		});
	
	//Fixes form CSS -> Spacing
		fixFormCSS();
		function fixFormCSS()
		{
			$("form").each(function(){
				//Returns the width of all label elements in form
				var labels = $(this).find("label").map(function(){
					//console.log($(this).width());
					return $(this).width();
				}).get();
				//Gets the widest label element
				var maxWidth = Math.max.apply(null, labels);
				//Sets the input's left properties according to the widest label element
				$(this).find("label").siblings("input").each(function(i) {
					$(this).css({
						position:"absolute",
						left:maxWidth+"px"
					});
					//Determines the top property (according to the element's index)
					if(i === 0)
					{
						$(this).css({
							top:0
						});
					}
					else
					{
						var $prev = $(this).prev("input");
						$(this).css({
							top:(parseInt($prev.css("top"), 10)+$prev.outerHeight(true))+"px"
						});
					}
				});
			});
		}
	
	//Opens modal inputs -> i.e. Add Transcript button for professor (when a field can be duplicated)
		$(".openModalInputs").click(function() {
			$(this).siblings(".modalInputs").slideToggle();
		});
	
	//Adds input (clones the previous input element with attributes and removes its value and then places it before the button that adds the input -> .addInput)
		$(".addInput").click(function() {
			var $clone = $(this).prev("input").clone(true);
			$(this).before($clone.val(""));
			var $parent = $(this).parents(".modalInputs");
			$parent.scrollTop(1E10);
		});
	
	//Same function as the Adds input function above, works on return carriage character keyup; Clones the element after itself
		$(".duplicateMe").keyup(function(e) {
			if(e.which == 13)
			{
				var $clone = $(this).clone(true);
				$(this).after($clone.val(""));
				$(this).next("input").focus();
				var $parent = $(this).parents(".modalInputs");
				$parent.scrollTop(1E10);
			}
		});
}

//Contains form-specific functions
function forms() {
		var phpfolder = "../php/"; //Location of the php folder in relation to this file
		
		//Sends message to user -> used with AJAX to receive a preconfigured object and return a message
		function sendMessageToUser(data)
		{
			console.log(data);
			var m = "";
			$.each(data.message, function(k, v) {
				m += "<span>"+k+": "+data.message[k]+"</span><br/>";
			});
			//If data.type is 1, feedback has been provided (the code was ran successfully)
			//If data.type is 0, error has occurred
			switch(data.type)
			{
				case 1:
					$("#feedback .message").html(m);
					$("#error").slideUp();
					$("#feedback").slideDown();
					break;
				case 0:
				default:
					$("#error .message").html(m);
					$("#feedback").slideUp();
					$("#error").slideDown();
					break;
			}
		}
		//Hides the feedback message on click
		$("#feedback").click(function(){
			$(this).slideUp();
		});
		//Hides the error message on click
		$("#error").click(function(){
			$(this).slideUp();
		});
		
		//Submits the form when the input type button with class .submit has been clicked
		$(".submit").click(function(){
			submitForm($(this));
		});
		
		//Confirm function -> i.e. used in deletion of database entries
		$(".confirm").click(function() {
			//Confirms deletion
			if(confirm("Are you sure you want to delete this Assignment?\nThis action cannot be undone!"))
			{
				//Submits the form
				submitForm($(this));
				return true;
			}
			else
			{
				return false;
			}
		});
		
		//Submits the form according to the form attributes.
		//Form serialize is used
		//Returns message to user after completion
		//Loading bar or gif could be implemented
		function submitForm($this)
		{
			$form = $this.parents("form");
			$.ajax({
				method: $form.attr("method"),
				url: phpfolder+$form.attr("action"),
				data: $form.serialize(),
				success: function(data) {
					sendMessageToUser($.parseJSON(data));
				}
			});
		}
}