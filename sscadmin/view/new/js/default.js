layui.use('form',function(){var form = layui.form;}); // 初始化

/** 初始化时间控件 */
layui.use('laydate', function(){
	var laydate = layui.laydate;
	laydate.render({
	  elem: '#user_regdate_start'
	});
	laydate.render({
	  elem: '#user_regdate_end'
	});
});

/** 用户管理页面数据表格 */
layui.use('table', function(){
	var table = layui.table;
	
	table.init('loadUserData', {
	  id: "userDataGrid",
	  height: 673, //设置高度
	  page: true,
	  limit: 15, //
	  limits: [15,30,45,60,75,90]
	});
	
	//监听工具条事件
	table.on('tool(loadUserData)', function(obj){
		var rowData = obj.data; // 获取当前行；
	    var layEvent = obj.event; // 获取对应的事件,也就是lay-event对应的值
	    var tr = obj.tr; // 获取当前行的DOM
	    
	    debugger
	    if('userConfig' == layEvent){
	    	layer.open({
    		  type: 2,
    		  title: '<span>' + rowData.userNickName +'<span>' + '  <h>用户资料</h>',
    		  area: ['1067px', '794px'], //宽高
    		  fixed: false, //不固定
    		  maxmin: true,
    		  content: '/view/new/user_config_info.html?userId=' + rowData.userId
    		});
	    }
	});
	
});

/** 域名管理页面域名数据表格 */
layui.use('table', function(){
	var table = layui.table;

	table.init('loadDomainData', {
	  id: "domainDataGrid",
	  height: 667, //...
	  page: true,
	  limit: 15 //...
	});

    // 监听工具条；
	table.on('tool(loadDomainData)', function(obj){
	    var rowData = obj.data; // 获取当前行；
	    var layEvent = obj.event; // 获取对应的事件,也就是lay-event对应的值
	    var tr = obj.tr; // 获取当前行的DOM
	    debugger
	    if('promotionCode' == layEvent){
		    // 推广码事件
	    	layer.open({
	  		  type: 2,
	  		  title: '',
	  		  area: ['1067px', '669px'], //宽高
	  		  fixed: false, //不固定
	  		  content: '/view/new/domain_promotion_code.html'
	  		});
	    }else if('edit' == layEvent){
		    // 编辑事件
	    	var contentForm = [];
	    	contentForm.push('<form id="" class="" action="" >');
	    	contentForm.push('<div id="domain_info_edit"><div class="domain-bind-title"><span>编辑域名</span></div>');
	    	contentForm.push('<hr style="width: 95%;position: absolute;top: 45px;left: 30px;color: silver;">');
	    	contentForm.push('<div id="domain_edit_proxy_acct" class="domain-bind-title"><span>代理账号： UserName</span></div><div id="domain_edit_proxy_name" class="domain-bind-title">');
	    	contentForm.push('<span>代理域名：</span><input type="text" name="" class="user-condition-input" value="www.8888888.com"/></div>');
	    	contentForm.push('<div id="domain_edit_acct_op" class="domain-bind-title"><span>操&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;作：</span>');
	    	contentForm.push('<select name="" class="user-config-tab1"><option value="0">启用</option><option value="1">禁用</option></select></div>');
	    	contentForm.push('<hr style="width: 95%;position: absolute;top: 240px;left: 30px;color: silver;">');
	    	contentForm.push('<div id="domain_edit_button"><span class="layui-btn layui-btn-danger layui-btn-radius" lay-submit="" lay-filter="confirmEdited">确认</span>');
	    	contentForm.push('<span class="layui-btn layui-btn-normal layui-btn-radius" onclick="javascript:layer.closeAll();">取消</span></div></div></form>');
	    	var contentFormStr = contentForm.join("");
	    	layer.open({
	    	  type: 1,
	    	  title: '',
	    	  area: ['1060px', '670px'], //宽高
	    	  content: contentFormStr
	    	});
	    	
	    	layui.use('form', function(){
	    	  var form = layui.form; 
	    	  form.render();
	    	  // 监听提交，data==form表单
	    	  form.on('submit(confirmEdited)',function(data){
	    		  debugger
	    		  layer.load();// 显示加载
	    		  $.ajax({
	    			  url: '',
	    			  data: data.field,
	    			  success: function(result){
	    				  if(result){
	    					  layer.closeAll('loading');// 关闭加载动画
	    				  }
	    			  }
	    		  });
	    		  layer.closeAll(); // 关闭弹窗
	    	  });
	    	  
	    	});
	    }else{
		    // 解绑事件
	    }
	  
	    // 同步更新缓存对应的值
	    obj.update({
		  
	    });
  });
});

/** 点击导航栏头像事件  */
function clickUserPic(){
	var obj = $("#dc_head_personal_info");
	if(obj.attr("style")){
		obj.show();
	}else{
		obj.hide();
	}
}
/** 用户管理重置按钮 */
function resetUserForm() {
	$("#user_name").val("");
	$("#user_nickname").val("");
	$("#user_phone_no").val("");
	$("#user_bank_no").val("");
	$("#user_regdate_start").val("");
	$("#user_regdate_end").val("");
	$("#user_select_usertype").val("0");
	$("#user_select_userlive").val("0");
	$("#select_proxy").val("");
	$("#user_select_isonline").val("0");
}

/** 用户管理-新增用户页面 */
function addUserToSystem() {
	var contentForm = new Array();
	contentForm.push('<form id="" class="layui-form-pane" action="" >');
	contentForm.push('<div id="dc_user_adduser"><div id="add_user_nickname" class="layui-form-item">');
	contentForm.push('<label class="layui-form-label">用户名</label><div class="layui-input-inline">');
	contentForm.push('<input type="text" name="userNickname" lay-verify="required" autocomplete="off" class="layui-input"></div></div>');
	contentForm.push('<div id="add_user_username" class="layui-form-item"><label class="layui-form-label">姓名</label><div class="layui-input-inline">');
	contentForm.push('<input type="text" name="userName" lay-verify="required" autocomplete="off" class="layui-input"></div></div>');
	contentForm.push('<div id="add_user_phoneno" class="layui-inline"><label class="layui-form-label">手机号码</label><div class="layui-input-inline">');
	contentForm.push('<input type="tel" name="phoneNo" lay-verify="required|phone" autocomplete="off" class="layui-input"></div></div>');
	contentForm.push('<div id= "add_user_email"class="layui-inline"><label class="layui-form-label">邮箱</label><div class="layui-input-inline">');
	contentForm.push('<input type="text" name="email" lay-verify="email" autocomplete="off" class="layui-input"></div></div>');
	contentForm.push('<div id="add_user_usertype" class="layui-form-item"><label class="layui-form-label">用户类型</label><div class="layui-input-inline">');
	contentForm.push('<select name="userType" lay-verify="required" class="layui-input"><option value="0">代理</option><option value="1">会员</option></select><i class="layui-edge"></i></div></div>');
	contentForm.push('<div id="add_user_password" class="layui-form-item"><label class="layui-form-label">登录密码</label><div class="layui-input-inline">');
	contentForm.push('<input type="password" name="loginPassword"  lay-verify="required" lay-verify="pass" placeholder="请输入密码" autocomplete="off" class="layui-input"></div></div>');
	contentForm.push('<div id="add_user_password_again" class="layui-form-item"><label class="layui-form-label">确认密码</label><div class="layui-input-inline">');
	contentForm.push('<input type="password" name=""  lay-verify="required" lay-verify="pass" placeholder="请再次输入密码" autocomplete="off" class="layui-input"></div></div>');
	contentForm.push('<div id="add_user_remark" class="layui-form-item"><label class="layui-form-label">备注</label><div class="layui-input-inline">');
	contentForm.push('<input type="text" name="remark" autocomplete="off" class="layui-input"></div></div>');
	contentForm.push('<span id="add_user_submit" class="layui-btn layui-btn-radius layui-btn-danger" lay-submit="" lay-filter="addUserForm">确认</span> ');
	contentForm.push('<span id="add_user_back" class="layui-btn layui-btn-radius layui-btn-normal" onclick="javascript:layer.closeAll();">返回</span></div></form> ');
	var contentFormStr = contentForm.join("");
	layer.open({
	  type: 1,
	  title: '新增会员',
	  area: ['1005px', '560px'], //宽高
	  content: contentFormStr
	});
	
	layui.use('form', function(){
	  var form = layui.form; 
	  form.render();
	  // 监听提交，data==form表单
	  form.on('submit(addUserForm)',function(data){
		  debugger
		  layer.load();// 显示加载
		  $.ajax({
			  url: '',
			  data: data.field,
			  success: function(result){
				  if(result){
					  layer.closeAll('loading');// 关闭加载动画
				  }
			  }
		  });
		  layer.closeAll(); // 关闭弹窗
	  });
	});
}

/** 用户管理-锁定用户按钮 */
function lockUsers() {
	var table=layui.table.cache.userDataGrid;
	var selectedData = [];
	if(table){
		$.each(table, function(i, item){
			if(item.LAY_CHECKED){
				selectedData.push(item);
			}
		});
		if(selectedData.length > 0){
			layer.confirm('确定要锁定选中用户吗？', function(yesBtn){
				if(yesBtn){
					$.ajax({
						//....
					});
				}
			});
		}else{
			layer.msg("没有选择任何条目！");
		}
	}else{
		layer.msg("没有选择任何条目！");
	}
}

/** 用户绑定域名 */
function bindDomain4Users() {
	layer.open({
	  type: 2,
	  title: '',
	  area: ['1067px', '669px'], //宽高
	  fixed: false, //不固定
	  content: '/view/new/domain_bindUser.html'
	});
}

/** 批量新增域名 */
function addDomainList() {
	layer.open({
	  type: 2,
	  title: '',
	  area: ['1067px', '669px'], //宽高
	  fixed: false, //不固定
	  content: '/view/new/batch_add_domain.html'
	});
}

/** 新增层级 */
function addNewLevel() {
	var contentForm = [];
	contentForm.push('<form id="" class="layui-form layui-form-pane" action="post" >');
	contentForm.push('<div class="domain-bind-title"><span>新增层级</span></div><hr style="width: 90%;position: absolute;top: 45px;left: 30px;">');
	contentForm.push('<div id="add_level_domain_name"><span class="domain-bind-address-div">层级名称：</span><input type="text" name="" class="user-condition-input"/></div>');
	contentForm.push('<div id="add_level_description"><span class="domain-bind-address-div">描述：</span><input type="text" name="" class="user-condition-input"/></div>');
	contentForm.push('<div id="add_level_bouns_count"><span class="domain-bind-address-div">存款次数：</span><input type="text" name="" class="user-condition-input"/></div>');
	contentForm.push('<div id="add_level_total_amount"><span class="domain-bind-address-div">存款总额：</span><input type="text" name="" class="user-condition-input"/></div>');
	contentForm.push('<div id="add_level_bet_lottuy"><span class="domain-bind-address-div">投注反水(彩票)：</span><input type="text" name="" class="user-condition-input"/></div>');
	contentForm.push('<div id="add_level_bet_ele"><span class="domain-bind-address-div">投注反水(电子)：</span><input type="text" name="" class="user-condition-input"/></div>');
	contentForm.push('<div id="add_level_reg_start"><span class="domain-bind-address-div">用户注册时间：</span><input id="levelDate1" type="text" name="" class="user-condition-input"/>');
	contentForm.push('<img src="/view/new/img/date.png"></div>');
	contentForm.push('<div id="add_level_reg_end"><span class="domain-bind-address-div">至：</span><input id="levelDate2" type="text" name="" class="user-condition-input"/>');
	contentForm.push('<img src="/view/new/img/date.png"></div>');
	contentForm.push('<div id="add_level_bouns_start"><span class="domain-bind-address-div">用户存款时间：</span><input id="levelDate3" type="text" name="" class="user-condition-input"/>');
	contentForm.push('<img src="/view/new/img/date.png"></div>');
	contentForm.push('<div id="add_level_bouns_end"><span class="domain-bind-address-div">至：</span><input id="levelDate4" type="text" name="" class="user-condition-input"/>');
	contentForm.push('<img src="/view/new/img/date.png"></div><hr style="width: 90%;position: absolute;top: 290px;left: 30px;">');
	contentForm.push('<div id="add_level_pop_button"><span class="layui-btn layui-btn-danger layui-btn-radius" lay-submit="" lay-filter="confirmAdded">确认</span>');
	contentForm.push('<span class="layui-btn layui-btn-normal layui-btn-radius" onclick="javascript:layer.closeAll();">取消</span></div></form>');
	var contentFormStr = contentForm.join("");
	layer.open({
	  type: 1,
	  title: '新增层级',
	  area: ['780px', '460px'], //宽高
	  content: contentFormStr
	});
	layui.use('laydate', function(){
		var laydate = layui.laydate;
		laydate.render({
		  elem: '#levelDate1'
		});
		laydate.render({
		  elem: '#levelDate2'
		});
		laydate.render({
		  elem: '#levelDate3'
		});
		laydate.render({
		  elem: '#levelDate4'
		});
	});
	layui.use('form', function(){
	  var form = layui.form; 
	  form.render();
	  // 监听提交，data==form表单
	  form.on('submit(confirmAdded)',function(data){
		  debugger
		  layer.load();// 显示加载
		  $.ajax({
			  url: '',
			  data: data.field,
			  success: function(result){
				  if(result){
					  layer.closeAll('loading');// 关闭加载动画
				  }
			  }
		  });
		  layer.closeAll(); // 关闭弹窗
	  });
	});
}

function refTodayDetail() {
	var todayUsers = $("#today_users");
	var todayNewUsers = $("#today_new_users");
	var onlineUsers = $("#online_users");
	todayUsers.hide();
	todayNewUsers.hide();
	onlineUsers.hide();
	// ...重刷新<span>中的数据
	
	setTimeout(function(){
		todayUsers.show();
		todayNewUsers.show();
		onlineUsers.show();
	}, 500);
}

function refreshFinanceToday() {
	var totalIn = $("#today_money_in");
	var totalOut = $("#today_money_out");
	var betTotal = $("#betting_total");
	var winTotal = $("#winning_total");
	totalIn.hide();
	totalOut.hide();
	betTotal.hide();
	winTotal.hide();
	// ...重刷新<span>中的数据
	
	setTimeout(function(){
		totalIn.show();
		totalOut.show();
		betTotal.show();
		winTotal.show();
	}, 500);
}






