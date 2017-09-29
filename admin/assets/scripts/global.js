$(document).ready(function(){
    $("ul.sf-menu").superfish();

    $(".sortable").sortable({
        revert: true
    });

    $("#search").click(function() {
        $("#searchdrop").toggle("slow");
        return false;
    });

    $(".notification").click(function() {
        $(this).fadeOut("slow");
    });
    
    // Accordion
    $(".accordion").accordion({
        header: "h3"
    });

    // Tabs
    $('.tabs').tabs();

    // Dialog			
    $('#dialog').dialog({
        autoOpen: false,
        width: 600,
        buttons: {
            "Ok": function() {
                $(this).dialog("close");
            },
            "Cancel": function() {
                $(this).dialog("close");
            }
        },
        modal: true
    });

    // Dialog Link
    $('#dialog_link').button().click(function() {
        $('#dialog').dialog('open');
        return false;
    });

    // Datepicker
    $('#datepicker').datepicker().children().show();

    // Horizontal Slider
    $('#horizSlider').slider({
        range: true,
        values: [17, 67]
    })

    // Vertical Slider				
    $("#eq > span").each(function() {
        var value = parseInt($(this).text());
        $(this).empty().slider({
            value: value,
            range: "min",
            animate: true,
            orientation: "vertical"
        });
    });

    //hover states on the static widgets
    $('#dialog_link, ul#icons li').hover(
        function() {
            $(this).addClass('ui-state-hover');
        },
        function() {
            $(this).removeClass('ui-state-hover');
        }
        );

    // Button
    $("#divButton, #linkButton, #submitButton, #inputButton").button();

    // Icon Buttons
    $("#leftIconButton").button({
        icons: {
            primary: 'ui-icon-wrench'
        }
    });

    $("#bothIconButton").button({
        icons: {
            primary: 'ui-icon-wrench',
            secondary: 'ui-icon-triangle-1-s'
        }
    });

    // Button Set
    $("#radio1").buttonset();


    // Progressbar
    $("#progressbar").progressbar({
        value: 37
    }).width(500);
    $("#animateProgress").click(function(event) {
        var randNum = Math.random() * 90;
        $("#progressbar div").animate({
            width: randNum + "%"
        });
        event.preventDefault();
    });

    //Tooltips
    $("[rel=tooltips]").twipsy({
        "placement": "right",
        "offset": 5
    });

    //WYSIWYG Editor
    $(".cleditor").cleditor();

    //HTML5 Placeholder for lesser browsers. Uses jquery.placeholder.1.2.min.shrink.js
    $.Placeholder.init();

    //Uses formvalidator
    $("#form0, #form1, #form2").validationEngine();
});

timeOut = null;
function showError(msg, containerId)
{
    if(typeof(containerId) == "undefined")
    {
        containerId = 'notificationHeader';
    }
    
    $(".notification").hide();
    $('#'+containerId).hide().html('<span class="notification undone">'+msg+'</span>').show();
    assignNotificationEvents();
}

function showSuccess(msg, containerId)
{
    if(typeof(containerId) == "undefined")
    {
        containerId = 'notificationHeader';
    }
    
    $(".notification").hide();
    $('#'+containerId).hide().html('<span class="notification done">'+msg+'</span>').show();
    assignNotificationEvents();
}

function showInfo(msg, containerId)
{
    if(typeof(containerId) == "undefined")
    {
        containerId = 'notificationHeader';
    }
    
    $(".notification").hide();
    $('#'+containerId).hide().html('<span class="notification information">'+msg+'</span>').show();
}

function assignNotificationEvents()
{
    $(".notification").click(function() {
        $(this).fadeOut("slow");
    });
}

function resetOverlays()
{
    $(".ui-widget-overlay").css("z-index", "1001");
}

function getDefaultDialogWidth()
{
    if($(window).width() < 800)
    {
        return '97%';
    }

    return '800';
}

function convertToSlug(text)
{
	return text
		.toLowerCase()
		.replace(/ /g,'-')
		.replace(/[^\w-]+/g,'');
}