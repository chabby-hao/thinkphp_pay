var tout;

window.onload=function(){

	$(".loading").hide();
	$("#page1").show();
	$("#page1").addClass("pageOpen");

	$("table").css({"display":"block"});
	$(".support").css({"display":"block"});
	
	$(".inputAmount").on('touchstart', function(event){
		//$("table").css({"display":"block"});
		//$(".support").css({"display":"block"});
		$(".line").css({"display":"block"});
		$(".moneyNum img").attr({"src":"/Public/Wap/images/money_pay_active.png"});
	});

	/*$('.confirm').on('touchstart', function () {
		$(this).css({"background":"#e54b12"});

	});
	$('.confirm').on('touchend', function () {
		$(this).css({"background":"#ff5314"});

	});*/
	
	

	function clearColor(obj)
	{
		return function(){
			$(obj).css({"background":"#fff"});
			$(".cancel").css({"background":"#fff"});
		}
	}
	

	function clearAmout()
	{
		$(".moneyAmout").html('');
		$("#total_amount").val('');
	}

	
	function clearTout()
	{
		clearTimeout(tout);
	}
	
	function setTout()
	{
		tout = setTimeout(clearAmout,1500);
	}
	
	$('.number').on('touchstart', function () {
		$(this).css({"background":"#f0f2f5"});
		setTimeout(clearColor(this), 1500);
	});
	$('.number').on('touchend touchmove', function () {
		$(this).css({"background":"#fff"});

	});
	$('.dot').on('touchstart', function () {
		$(this).css({"background":"#f0f2f5"});
		setTimeout(clearColor(this), 1500);
	});
	$('.dot').on('touchend touchmove', function () {
		$(this).css({"background":"#fff"});

	});
	$('.cancel').on('touchstart', function () {
		$(this).css({"background":"#f0f2f5"});
		$(".cancel").css({"background":"#f0f2f5"});
		setTimeout(clearColor(this), 1500);
		setTout();
	});
	$('.cancel').on('touchend touchmove', function () {
		$(this).css({"background":"#fff"});
		$(".cancel").css({"background":"#fff"});
		clearTout();
	});
	
	
	$("table").on('touchstart', function(event){
		var FromElement = event.target;
		var str = $(".moneyAmout").html();

		if($(FromElement).hasClass("cancel")){//点击后退的处理函数
			var strL = str.length;
			if(strL > 0){
				str = str.substring(0,strL-1);
			}

		}else if($(FromElement).hasClass("confirm")){//点击确定后的处理函数

			
			if(!isSubmit){
			    //如果没有提交
			    var $pay = $(".confirm");
				$pay.css({backgroundColor:'#c1bfbf'});
				isSubmit = true;
				myform.submit();
			}
			
			/*$("table").css({"display":"none"});
			$(".support").css({"display":"none"});
			$(".line").css({"display":"none"});*/

			if(str.length == 0 || str == "0"){
				$(".moneyNum img").attr({"src":"/Public/Wap/images/money_pay.png"});
				str = "";
			}

			for(var i = str.length;i>0;i--){

				if(str.indexOf(".") != -1 && str.lastIndexOf("0") == str.length-1){

					str = str.substring(0,str.lastIndexOf("0"));

				}
			}

			if(str.indexOf(".") == str.length-1){
				str = str.substring(0,str.length-1);
			}

		}else{

			var strSplit = str.split('.');
			
			console.log(strSplit[0]);
			console.log(strSplit[1]);
			if(strSplit[1] != undefined && strSplit[1].length > 1){
				return ;
			}else if($(FromElement).html() == "0"){
				if(str.length == 1 && str == "0"){
					return ;
				}
			}else if($(FromElement).html() == "."){
				if(str.indexOf(".") != -1 || str.length == 0){
					return ;
				}
			}else if(strSplit[1] == undefined && strSplit[0].length> 4){
				return ;
			}else if(str == "0"){
				str = "";
			}
			str += $(FromElement).html();

		}
		$(".moneyNum img").attr({"src":"/Public/Wap/images/money_pay_active.png"});
		$(".moneyAmout").html(str);
		$("#total_amount").val(str);
		
		event.preventDefault();
	});

}