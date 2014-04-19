jQuery( document ).ready(function( $ ) {

	var $visibleUsers, $totalUsers;

	function userValidation(input){
		if(typeof(input)==='undefined') input = '.icp-UserValidation';

		$(input).typeWatch({
		    callback: function (value) { 
				var user = value;
				var $elem = $(this);
		    	var loading = $elem.next('.icp-live-icon').find('.icp-loading');
		    	var ok = $elem.next('.icp-live-icon').find('.icp-yes');
		    	var fail = $elem.next('.icp-live-icon').find('.icp-no');
		    	var hiddenInput = $elem.next().next('input[type=hidden]');
		        $.ajax({
					type: "POST",
					url: ajaxurl,
					data: { username: user, action: 'icp_check_user_id' },
					beforeSend: function () {
						fail.add(ok).hide();
						loading.show();
					},
		        	success:  function (response) {
		        		loading.hide();
		            	if(response=='false'){
		            		fail.show();
		            		hiddenInput.val('');
		            	} else {
		            		ok.show();
		            		hiddenInput.val(response);
		            	}
		        	}
				});	    
			},
		    wait: 400,
		    captureLength: 1
		});
	}

	userValidation();

	//function userValidation(){
	/*
	$('.icp-UserValidation').typing({
	    start: function (event, $elem) {
	        
	    },
	    stop: function (event, $elem) {
	    	var user = $elem.val();
	    	var loading = $elem.next('.icp-live-icon').find('.icp-loading');
	    	var ok = $elem.next('.icp-live-icon').find('.icp-yes');
	    	var fail = $elem.next('.icp-live-icon').find('.icp-no');
	    	var hiddenInput = $elem.next().next('input[type=hidden]');
	        $.ajax({
				type: "POST",
				url: ajaxurl,
				data: { username: user, action: 'icp_check_user_id' },
				beforeSend: function () {
					fail.add(ok).hide();
					loading.show();
				},
	        	success:  function (response) {
	        		loading.hide();
	            	if(response=='false'){
	            		fail.show();
	            		hiddenInput.val('');
	            	} else {
	            		ok.show();
	            		hiddenInput.val(response);
	            	}
	        	}
			});
	    },
	    delay: 400
	});		
	*/
	//}

	$totalUsers = 10;

	$visibleUsers = $('.icp_user_hashtag:visible').length;

	icpUpdateContent();

	var clonedField = $('#icpMainTable .icp_user_hashtag:first');
	var separator	= $('#icpMainTable .icp_user_tr:first');
	var userContainer = $('#icpMainTable > tbody');

	$('#icp-addUser').on('click', function(e){
		e.preventDefault();
		
		if($visibleUsers===0){
			$('#icpMainTable .icp_user_hashtag:first:hidden').show().removeClass('hidden');
			$('#icpMainTable .icp_user_tr:first:hidden').show().removeClass('hidden');
			console.log('remove hidden');
		} else {
			var clone = clonedField.clone();
			clone.find('.icp-trash').hide();
			clone.find('.icp-live-icon img').hide();
			clone.find('input').val('');
			userContainer.append(clone);
			userValidation(clone.find('input.icp-UserValidation'));
			userContainer.append(separator.clone());
		}
		$visibleUsers++;
		icpUpdateContent();
	});


	$(document).on('click', '.icp-trash', function(e){
		e.preventDefault();
		if($visibleUsers===1){
			$(this).parents('.icp_user_hashtag').hide();
			$(this).parents('.icp_user_hashtag').next('tr').hide();
			$(this).parents('.icp_user_hashtag').find('input').val('');
		} else {
			$(this).parents('.icp_user_hashtag').next('tr').remove();
			$(this).parents('.icp_user_hashtag').remove();
		}
		
		$visibleUsers--;
		icpUpdateContent();
	});

	function icpUpdateContent(){
		if($visibleUsers>=1){
			$('#icpUserLabel').html('"Add Another Team Member"');
			$('#icpUserNumber').html('more');
			$('#icp-addUser').html('Add Another Team Member');
		} else {
			$('#icpUserLabel').html('"Add New Team Member"');
			$('#icpUserNumber').html('your first one');
			$('#icp-addUser').html('Add New Team Member');			
		}
		if($totalUsers==$visibleUsers){
			$('#icp-addUser').hide();
		} else {
			$('#icp-addUser').show();
		}
	}

	$('#icp-unlinkAccount').on('click', function(){
		var icpConfirm = confirm("Are you sure you want to do this?");
		return icpConfirm;
	});

	var somethingChanged = false;

    $('.icp_settings_form input, .icp_settings_form select, .icp_settings_form textarea').change(function() { 
        somethingChanged = true; 
    }); 

	$('.icpNavTab a').on('click', function(){
		if(somethingChanged){
			var icpConfirm = confirm("You have pending changes. Please save them or they will be lost. Do you want to continue without saving?");
			return icpConfirm;
		} else {
			return true;
		}
	});

	$(document).on('mouseenter', '.icp-hover-table', function () {
	    $(this).find('.icp-trash').fadeIn('fast');
	}).on('mouseleave', '.icp-hover-table',  function () {
	    $(this).find('.icp-trash').fadeOut('fast');
	});

});