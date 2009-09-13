/* Show user-info plugin script */



if (window.rcmail) {

    rcmail.addEventListener('init', function(evt) {
    
    rcmail.register_command('plugin.vacation-save', function() { 
	    document.forms.vacationform.submit();

    }, true);


     var tab = $('<span>').attr('id', 'settingstabpluginvacation').addClass('tablink');
    rcmail.add_element(tab, 'tabs');
    var button = $('<a>').attr('href', rcmail.env.comm_path+'&_action=plugin.vacation').html(rcmail.gettext('vacation')).appendTo(tab);
    button.bind('click', function(e){ return rcmail.command('plugin.password', this) });



    // add button and register command
   
    rcmail.register_command('plugin.vacation', function(){ rcmail.goto_url('plugin.vacation') }, true);
   
});
}