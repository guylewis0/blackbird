/* $Id$ */

/*

Class: cms

Main javascript class for use with blackbird.

*/

function cms(options)
{
	this.data = new Object();
	for(var i in options){
		this.data[i] = options[i];
	}
	
	this.callbacks = new Object();	
	this.broadcaster = new EventBroadcaster();
	this.broadcaster.addListener(this);
	
	this.clickA = new Array();
	this.fadeInterval = undefined;
	this.lastSection = 'main';
	
	//this design is weak sauce, get a new loader
	var myGlobalHandlers = {
		onCreate: function(){
			$('ajax').show();
		},
		onComplete: function() {
			if(Ajax.activeRequestCount == 0){
				$('ajax').hide();
			}
		}
	};
	
	Ajax.Responders.register(myGlobalHandlers);

}
var CMS = new cms();


/*

Method: setProperty

Getter/Setter into internal object

Parameters:

	prop - property name
	value - value

*/

cms.prototype.setProperty = function(prop,value)
{
	this.data[prop] = value;
};

/*

Method: getProperty

Getter/Setter from internal object

Parameters:

	prop - property name
	
Returns:

	value

*/

cms.prototype.getProperty = function(prop)
{
	return this.data[prop];
};

/*

Method: addCallback

Adds a callback obj to a namespace to handle AJAX events

Parameters:

	name_space - name space
	obj - object reference
	method - method name

*/

cms.prototype.addCallback = function(name_space,obj,method)
{
	this.callbacks[name_space] = { obj: obj, method: method };
};

/*

Method: onRemoteComplete

Fires when remote operations are complete and closes record container

Parameters:

	obj - object reference

*/

cms.prototype.onRemoteComplete = function(obj)
{
	if($('ajax')){
		$('ajax').hide();
	}
	var listener = this.callbacks[obj.name_space].obj;
	var method = this.callbacks[obj.name_space].method;
	
	this.closeRecord(obj.name_space);
	
	if(listener[method]){
		listener[method].apply(listener,[obj]);
	}
};

/*

Method:	onRemoteErrors

Handles errors from remote script operations

Parameters:

	obj - object reference

*/

cms.prototype.onRemoteErrors = function(obj)
{
	if($('ajax')){
		$('ajax').hide();
	}

	this.showTab(obj.name_space);

	for(var i in obj.errors){
		var elem = obj.name_space + '_' + obj.errors[i][0];
		
		var newobj = 'error_' + obj.name_space + '_' + obj.errors[i][0];
		
		if($(newobj)){
			//update interior content yo
			$(newobj).update(obj.errors[i][1]);
		}else{
			new Insertion.After($(elem), '<div id="' + newobj + '" class="error">' + obj.errors[i][1] + '</div>');
		}
			
		var label = $('form_' + obj.name_space).getElementsBySelector('label[for="' + elem + '"]');
		label[0].addClassName('error');
		label[0].style.color = '#CC3333';
	}
	
	if(obj.name_space != 'main'){
		//show form buttons
		var tA = $('pane_' + obj.name_space).select('.buttons');
		var obj = tA[0];
		obj.show();
		//new Effect.Opacity(obj, {duration:0.5, from:0.2, to:1.0});
	}
	if(obj.name_space == 'main'){
		$('edit_buttons').show();	
	}
	
};


/**
*	onSubmit
*
*
*/

cms.prototype.onSubmit = function()
{
	if($('ajax')){
		$('ajax').show();
	}
};

/**
*	submitRelated
*
*
*/

cms.prototype.submitRelated = function(name_space)
{	
	var errorsA = this.validate(name_space);
	if(errorsA == true){
		
		if(CMS.broadcaster != undefined){
			CMS.broadcaster.broadcastMessage("onSubmit");
		}
		
		$('pane_' + name_space).select('.buttons')[0].hide();
		
	}
	if(errorsA.length > 0){
		this.handleErrors(errorsA);
	}
};

/**
*	submitMain
*
*
*/

cms.prototype.submitMain = function(name_space)
{
	this.showTab('main');
	var tA = this.validate(name_space);
	if(tA == true){
		$('edit_buttons').hide();
	}
	if(tA.length > 0){
		this.showTab('main');
		this.handleErrors(tA);
	}
};

/**
*	loadUrl
*
*
*/

cms.prototype.loadUrl = function(url)
{
	window.location = url;
};

/**
*	handleErrors
*
*
*/

cms.prototype.handleErrors = function(obj)
{
	var t = '';
	var iMax = obj.length;
	for(var i=0;i<iMax;i++){
		t += obj[i].message + '\n';
	}
	alert(t);
};

//changeme12345

/**
*	validate
*
*
*/

cms.prototype.validate = function(name_space)
{
	return validate($('form_' + name_space),name_space);
};

/**
*	addNewRecord
*
*
*/

cms.prototype.addNewRecord = function(table,name_space)
{

	this.recordHandler(table,'',name_space,'add',this.processAdd,'insert');
	this.broadcaster.broadcastMessage("onAddNew");
	
};

/**
*	editRecord
*
*
*/

cms.prototype.editRecord = function(table,id,name_space,elem)
{
	this.recordHandler(table,id,name_space,'edit',this.processEdit,'update');
};

/**
*	deleteRecord
*
*
*/

cms.prototype.deleteRecord = function(table,id,name_space)
{

	var answer = confirm ("Really Delete?");
	if (answer) {
		
		this.data.name_space = name_space;
		
		var sendVars = new Object();
		sendVars.name_space = name_space;
		sendVars.action = 'deleteRecord';
		sendVars.table = table;
		sendVars.id = id;
		
		var myAjax = new Ajax.Request(
			this.data.cms_root + 'ajax', 
			{
				method		: 'post', 
				parameters	: formatPost(sendVars),
				onComplete	: this.processDelete.bind(this)
			}
		);
	
	}
		
};

/**
*	processDelete
*
*
*/

cms.prototype.processDelete = function()
{
		
	var tA = $('pane_' + this.data.name_space).select('.edit_form');
	var obj = tA[0];
	
	if (obj.style.display == 'none') {
	
	}else{
		this.closeRecord(this.data.name_space);
	}
	
	//possible use of event broadcaster here
	
	//
	//cmsBroadcaster.broadcastMessage("onDelete");
	eval("data_grid_" + this.data.name_space + ".getUpdate();");
};

/**
*	recordHandler
*	central gateway for all datagrid requests
*
*/

cms.prototype.recordHandler = function(table,id,name_space,mode,handler,query_action)
{

	var sendVars = new Object();
			
	sendVars.query_action = query_action;
	sendVars.mode = query_action;
	sendVars.table = table;
	sendVars.id = id;
	sendVars.id_parent = this.data.id_parent;
	sendVars.action = 'editRecord';
	sendVars.name_space = name_space;
	sendVars.table_parent = this.data.table_parent;
	
	
	this.data.name_space = name_space;
	
	var tA = $('pane_' + this.data.name_space).select('.detail');
	
	var obj = $(tA[0]);

	var _scope = this;
	
	var myAjax = new Ajax.Updater(
		obj,
		this.data.cms_root + 'ajax', 
		{
			method			: 'post', 
			parameters		: formatPost(sendVars),
			onComplete		: handler.bind(this),
			evalScripts 	: true
		}
	);
		
};

/**
*	processAdd
*
*
*/

cms.prototype.processAdd = function()
{
	this.openRecord(this.data.name_space);
};

/**
*	processEdit
*
*
*/

cms.prototype.processEdit = function()
{
	this.openRecord(this.data.name_space);
};

/**
*	openRecord
*
*
*/

cms.prototype.openRecord = function(name_space)
{
	var tA = $('pane_' + name_space).select('.edit_form');
		
	var obj = $(tA[0]);
	if (obj.style.display == 'none') {
		Effect.SlideDown(obj, {duration: .5});
	}
	
	this.broadcaster.broadcastMessage("onOpen");	
	
};

/**
*	closeRecord
*
*
*/

cms.prototype.closeRecord = function(name_space)
{
	
	var tA = $('pane_' + name_space).select('.edit_form');
	var obj = tA[0];
	Effect.SlideUp(obj, {duration: .5});
	
	this.broadcaster.broadcastMessage("onClose");

};

cms.prototype.closeMessage = function()
{
	$('message_content').remove();
};

/**
*	registerClick
*
*
*/

cms.prototype.registerClick = function(obj)
{
	
	var inset = false;
	var iMax = this.clickA.length;
	for(var i=0;i<iMax;i++){
		if(this.clickA[i] == obj.id){
			if(obj.checked == false){
				this.clickA.splice(i,1);
			}
			inset = true;
			break;
		}			
	}
	if(inset == false){
		if(obj.checked == true){
			this.clickA.push(obj.id);
		}
	}
	if(this.clickA.length > 0){
		$('selection_set').innerHTML = 'With Selected ' + this.clickA.length;
	}else{
		$('selection_set').innerHTML = 'With Selected';
	}
};

/**
*	checkAll
*
*
*/

cms.prototype.checkAll = function(mode)
{
	//get elements
	var itemA = $('main').select('.data_grid_checkbox');
	for(var i in itemA){
		var obj = itemA[i];
		obj.checked = mode;
		//register with click controller
		if(obj.id != undefined){
			this.registerClick(obj);
		}
	}
};

/**
*	toggleTabs
*
*
*/

cms.prototype.toggleTabs = function()
{
	var tA = $('edit_nav').select('.trigger');
	var iMax = tA.length;
	for (i=0;i<iMax;i++) {
		//alert(triggers[i].id);
		toggle = tA[i].id.replace('tab_','');
		//alert(toggle);
		Event.observe(tA[i], 'click', function(){alert(toggle);});
	}
};

/**
*	showTab
*
*
*/

cms.prototype.showTab = function(tab)
{
	var tA = $('edit_nav').select('.trigger');
	var iMax = tA.length;
	for(var i=0;i<iMax; i++){
		
		var name_space = tA[i].id.replace('tab_','');
		var item = $('pane_' + name_space);

		if(item){
			if(tab == name_space){
				$('tab_' + name_space).addClassName('active');
				this.tab = item;
				this.tab.show();
			}else{
				item.hide();
				$('tab_' + name_space).removeClassName('active');
			}
			
		}
	}
	
	/*
	if(tab != this.lastSection){
		//check for old formController
		var cont = eval('formController_' + tab);
		if(cont != undefined){
			if(cont.getLength() > 0){
				alert('there are unsaved changes');
			}	
		}
	}
	*/
	
	this.lastSection = tab;
};


/**
*	searchDataGrid
*
*
*/

cms.prototype.searchDataGrid = function(){
	if($('search').value != 'Search...'){
		window.document.forms['searchrec'].submit();
	}
};

/**
*	viewRows
*
*
*/

cms.prototype.viewRows = function(obj,url){
	
	window.location = url + '&limit=' + obj.value;
	
};

/**
*	setFilter
*
*
*/

cms.prototype.setFilter = function(obj,url){
	if(obj.value != ''){
		window.location = url + '&' + obj.id + '=' + obj.value;
	}else{
		window.location = url;
	}
};

/**
*	batchProcess
*
*
*/

cms.prototype.batchProcess = function(table)
{
	if(this.clickA.length > 0){
		if($('batchProcess').value != ''){
			var idA = new Array();
			var iMax = this.clickA.length;
			for(var i=0;i<iMax;i++){
				idA.push(this.clickA[i].split("_")[1]);
			}
			window.location = this.data.cms_root + 'process/batch/' + table + '/?action='+ $('batchProcess').value + '&id_set=' + idA.join();
		}
	}else{
		$('batchProcess').value = '';
		alert('Select some rows first');
	}
	
};

/**
*	toggleSection
*
*
*/

cms.prototype.toggleSection = function(elem)
{
	//var obj = $(elem);
	
	var obj = $(elem).next();		
	if(obj.hasClassName('open')){
		Effect.BlindDown(obj,{duration:0.2});
		obj.removeClassName('open');
		
	}else{
		obj.addClassName('open');
		Effect.BlindUp(obj,{duration:0.2});
	}
};

/**
*	toggleHelp
*
*
*/

cms.prototype.toggleHelp = function()
{
	if ($('help')) {
		if ($('help').style.display == 'none') {
			Effect.SlideDown($('help'), {duration: .5});
		} else {
			Effect.SlideUp($('help'), {duration: .5});
		}
	} else {
		alert('Help documentation not found.');
	}
};


/**
*	loopBack
*
*
*/

cms.prototype.loopBack = function(name_space)
{
	$('loop_back').value = 'loop';
	var tA = this.validate(name_space);
	if(tA == true){
		$('edit_buttons').hide();
	}
	if(tA.length > 0){
		this.showTab('main');
		this.handleErrors(tA);
	}
};

/**
*	dirify
*
*
*/

cms.prototype.dirify = function(input)
{
	output = input.strip(); // strip whitespace
	output = output.gsub("[^a-zA-Z0-9 \_-]", ""); // only take alphanumerical characters, but keep the spaces too...
	output = output.gsub(" ", "_", output); // replace spaces by underscores
	output = output.toLowerCase();  // make it lowercase
	return output;
};

/**
*	createSlug
*
*
*/

cms.prototype.createSlug = function(elem,source)
{
	var elem = $(elem);
	// If a value doesn't already exist, generate the slug
	if (!elem.value) {
		if (source) {
			var source = $(source);
		} else {
			var source = elem.up(1).previous(0).down(2);
		}
		Event.observe(source,'keyup', function()
		{
			elem.value = CMS.dirify(source.value);
		}, true);
		
	}
	Event.observe(elem,'change', function()
	{
		elem.value = CMS.dirify(elem.value);
	}, true);
};

/**
*	suggestTag
*
*
*/

cms.prototype.suggestTag = function(elem,tags)
{
	elem = $(elem);
	suggestions = elem.id+'_suggestions';
	// Insert suggestion markup
	elem.insert({after:'<ul id="'+suggestions+'" class="tag_suggestions"><li>'+tags.join('</li><li>')+'</li></ul>'});
	// Update suggestions as the user types
	Event.observe(elem,'keyup', function()
	{
		input = this.value.split(',');
		// Find all possible suggestions
		suggest = tags.findAll(function(s) { return s.startsWith(input[input.length-1].strip()); });
		$(suggestions).update('<li>'+suggest.join('</li><li>')+'</li>');
	}, true);
};