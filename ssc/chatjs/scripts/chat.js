
//定义我们的hichat类
var HiChat = function() {
    this.socket = null;
};
var that;


window.onload = function() {
    var hichat = new HiChat();
    hichat.init();
};
function genRandom() {
    var text = "",
        possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    for( var i=0; i < 6; i++ ){
        text += possible.charAt(Math.floor(Math.random() * possible.length));
    }
    return text;
}
var maxstrlen = 300;
function Q(s) { return document.getElementById(s); }

function checkWord(c) {
    len = maxstrlen;
    var str = c.value,
        myLen = getStrleng(str);
    var wck = Q("wordCheck");

    if (myLen > len * 3) {
        c.value = str.substring(0, i + 1);
    }
    else {
        wck.innerHTML = Math.floor((len * 3 - myLen) /3);
    }
}

function getStrleng(str) {
   var myLen = 0,
    i = 0;
    for (; (i < str.length) && (myLen <= maxstrlen * 3); i++) {
        if (str.charCodeAt(i) > 0 && str.charCodeAt(i) < 128){
            myLen++;
        } else{
            myLen += 3;
        }
    }
    return myLen;
}
HiChat.prototype = {
    init: function() {//此方法初始化程序
        document.getElementById('loginWrapper').style.display = 'none';//隐藏遮罩层显聊天界面
        that = this;
        //建立到服务器的socket连接
        this.socket  = io(chatHost+'/?params='+chatToken);
        // io('http://localhost', {path: '/nodejs/socket.io'});
      //  this.socket  = io(chatHost, {path: '/nschat'+'?params='+chatTOkenString+'&curuser='+curNickName});
       // this.socket  = io(chatHost, {path: '/ns'+curUid});
      //  this.socket  = io().of('curUid');
        this.socket.connect();
        //监听socket的connect事件，此事件表示连接已经建立
        this.socket.on('connect', function() {
            //msg: 服务器正在连接
            // console.log('登录聊天室成功');
        });

        this.socket.on('throwException', function(info) {
            alert(info);
            // console.log(info);
        });
        this.socket.on('frontSyncLogout', function() {
            // console.log('frontSyncLogout');
            that.socket.disconnect();
            $('.userList').html('');
            alert("你已经退出，前往登录页面");
            // console.log(info);
        });
        this.socket.on('loginFailure', function(info) {
            // console.log(info);
            that.socket.disconnect();
             alert(info);
        });

        //提示登录成功
        this.socket.on('loginSuccess', function(username) {
            chatCurnickname = username;
            document.title = 'hichat | ' + username;
            document.getElementById('from').value = username;
            document.getElementById('loginWrapper').style.display = 'none';//隐藏遮罩层显聊天界面
            document.getElementById('messageInput').focus();//让消息输入框获得焦点
            that.socket.emit('requestOnlineUsers');//请求在线用户列表
            that.socket.on('to' + username, function(user, msg, color) {//接受私聊信息
                that._displayNewMsg(user, msg, color ,1);
            });
            that.socket.on('toImg' + username, function(user, img) {//接受私聊图片
                that._displayImage(user, img, 1);
            });
        });


        //推送到每个在线聊天室，告诉他们关联的用户加入或者离开了
        this.socket.on('system', function(nickName, userCount, type) {
            var msg = nickName + (type == 'login' ? ' 加入' : ' 离开');
            //指定系统消息显示为红色
            that._displayNewMsg('系统 ', msg, 'red', 0);
            // document.getElementById('status').textContent = userCount + ' 个用户在线';
            if(type == 'logout'){//如果用户离开则从在线列表中删除
                var className = "." + nickName;
                $(className).remove();
            }else if(type == 'login'){
              /*  if($("#from").val() != nickName){
                    $('<li class='+nickName+'><font class="userPrivate" >'+nickName+'</font></li>').appendTo(".userList");
                }*/
                $('.userPrivate').parent().unbind('click');
                $('.userPrivate').parent().bind('click',function(){
                    $('#to').val($(this).text());
                    $(this).addClass("cur").siblings().removeClass('cur');
                    parent.layer.alert('和'+ $('#to').val() + '进行对话',0);
                });
                $('.userAll').unbind('click');
                $('.userAll').bind('click',function(){
                    $('#to').val('');
                    alert('和全部成员进行对话');
                });
            }

        });

        //获取在线用户列表
        this.socket.on('getOnlineUsers', function(users) {
            var liStr = '';
            if(users.length != 0){
                $.each(users,function(k,v){
                    if( chatCurnickname != v.nickname ){
                        if(v.level < chatCurLevel){
                            liStr += '<dt data-level="上级" class='+ v.nickname+'><font class="userPrivate">'+ v.nickname+'</font></dt>';
                        }else if(v.level > chatCurLevel){
                            liStr += '<dd data-level="下级" class='+ v.nickname+'><font class="userPrivate">'+ v.nickname+'</font></dd>';
                        }
                    }
                });
                $('.userList').empty().append(liStr);

                $('.userPrivate').parent().unbind('click');
                $('.userPrivate').parent().bind('click',function(){
                    $('#to').val($(this).text());
                    $(this).addClass("cur").siblings().removeClass('cur');
                    parent.layer.alert('和'+ $('#to').val() + '进行对话',0);
                });
                $('.userAll').unbind('click');
                $('.userAll').bind('click',function(){
                    $('#to').val('');
                    alert('和全部成员进行对话');
                });
            }
            // document.getElementById('status').textContent = users.length + ' 个用户在线';
        });


        this.socket.on('newMsg', function(user, msg, color) {
            that._displayNewMsg(user, msg, color, 0);
        });


        this.socket.on('newImg', function(user, img) {
            that._displayImage(user, img, 0);
        });

        document.getElementById('sendImage').addEventListener('change', function() {
            //检查是否有文件被选中
            if (this.files.length != 0) {
                //获取文件并用FileReader进行读取
                var file = this.files[0],
                    reader = new FileReader();
                if (!reader) {
                    that._displayNewMsg('system', '!your browser doesn\'t support fileReader', 'red', 0);
                    this.value = '';
                    return;
                };
                reader.onload = function(e) {
                    //读取成功，显示到页面并发送到服务器
                    this.value = '';
                    if($('#to').val() != ''){//私聊发图
                        that.socket.emit('privateImg', e.target.result,$('#from').val(), $('#to').val());
                    }else{//公聊发图
                        parent.layer.alert('请选择一个用户');
                        // that.socket.emit('img', e.target.result);
                    }
                    // that._displayImage('me', e.target.result, 0);
                };
                reader.readAsDataURL(file);
            };
        }, false);
        this._initialEmoji();
        document.getElementById('emoji').addEventListener('click', function(e) {
            var emojiwrapper = document.getElementById('emojiWrapper');
            emojiwrapper.style.display = 'block';
            e.stopPropagation();
        }, false);

        document.body.addEventListener('click', function(e) {
            var emojiwrapper = document.getElementById('emojiWrapper');
            if (e.target != emojiwrapper) {
                emojiwrapper.style.display = 'none';
            };
        });

        document.getElementById('emojiWrapper').addEventListener('click', function(e) {
            //获取被点击的表情
            var target = e.target;
            if (target.nodeName.toLowerCase() == 'img') {
                var messageInput = document.getElementById('messageInput');
                messageInput.focus();
                messageInput.value = messageInput.value + '[emoji:' + target.title + ']';
            };
        }, false);

        document.getElementById('nicknameInput').addEventListener('keyup', function(e) {
            if (e.ctrlKey && e.which == 13) {
                var nickName = document.getElementById('nicknameInput').value;
                if (nickName.trim().length != 0) {
                    that.socket.emit('login', nickName);
                };
            };
        }, false);
        document.getElementById('messageInput').addEventListener('keyup', function(e) {
            var messageInput = document.getElementById('messageInput'),
                msg = messageInput.value,
                color = document.getElementById('colorStyle').value;
            if (e.ctrlKey && e.which == 13 && msg.trim().length != 0) {
                messageInput.value = '';
                //显示和发送时带上颜色值参数
                if($('#to').val() != ''){
                    that.socket.emit('privateMessage', $('#from').val(), $('#to').val(),msg,color);//发送私聊
                    that._displayNewMsg('我', msg, color, 0);
                }else{
                    parent.layer.alert('请选择一个用户');
                    // that.socket.emit('postMsg', msg, color);//发送群聊
                }

            }else if(e.ctrlKey && e.which == 13 && msg.trim() != ' '){
                parent.layer.alert('发送内容不能为空');
            };
        }, false);
    },
    _displayNewMsg: function(user, msg, color, type) {//type0 公聊 1 私聊
        var container = document.getElementById('historyMsg'),
            msgToDisplay = document.createElement('p'),
            date = new Date().toTimeString().substr(0, 8),
            toUser = $('#to').val();
        //将消息中的表情转换为图片
        msg = this._showEmoji(msg);
        msgToDisplay.style.color = color || '#000';
        if(type == 0){
            toUser = (user.trim()=='系统'|| toUser =='')?'':'(@'+$('#to').val()+')';
            msgToDisplay.innerHTML = user + toUser+'<span class="timespan">(' + date + '): </span>' + msg;
        }else{
            msgToDisplay.innerHTML = user + '(@我)<span class="timespan">(' + date + '): </span>' + msg;
        }

        container.appendChild(msgToDisplay);
        container.scrollTop = container.scrollHeight;
    },


    _displayImage: function(user, imgData, type) {
        var container = document.getElementById('historyMsg'),
            msgToDisplay = document.createElement('p'),
            date = new Date().toTimeString().substr(0, 8),
            toUser = $('#to').val();
        if(type == 0){
            toUser = (user.trim()=='系统'|| toUser =='')?'':'(@'+$('#to').val()+')';
            msgToDisplay.innerHTML = user + '<span class="timespan">(' + date + '): </span> <br/>' + '<a href="' + imgData + '" target="_blank"><img src="' + imgData + '"/></a>';
        }else{
            msgToDisplay.innerHTML = user + '(@我)<span class="timespan">(' + date + '): </span> <br/>' + '<a href="' + imgData + '" target="_blank"><img src="' + imgData + '"/></a>';
        }

        container.appendChild(msgToDisplay);
        container.scrollTop = container.scrollHeight;
    },

    _initialEmoji: function() {
        var emojiContainer = document.getElementById('emojiWrapper'),
            docFragment = document.createDocumentFragment();
        for (var i = 41; i > 0; i--) {
            var emojiItem = document.createElement('img');
            emojiItem.src = '../chatjs/front/emoji/' + i + '.gif';
            emojiItem.title = i;
            docFragment.appendChild(emojiItem);
        };
        emojiContainer.appendChild(docFragment);
    },

    _showEmoji: function(msg) {
        var match, result = msg,
            reg = /\[emoji:\d+\]/g,
            emojiIndex,
            totalEmojiNum = document.getElementById('emojiWrapper').children.length;
        while (match = reg.exec(msg)) {
            emojiIndex = match[0].slice(7, -1);
            if (emojiIndex > totalEmojiNum) {
                result = result.replace(match[0], '[X]');
            } else {
                result = result.replace(match[0], '<img class="emoji" src="../chatjs/front/emoji/' + emojiIndex + '.gif" />');
            };
        };
        return result;
    },
};


//昵称设置的确定按钮
document.getElementById('loginBtn').addEventListener('click', function() {
    var nickName = document.getElementById('nicknameInput').value;
    //检查昵称输入框是否为空
    if (nickName.trim().length != 0) {
        //不为空，则发起一个login事件并将输入的昵称发送到服务器
        that.socket.emit('login', nickName);

    } else {
        //否则输入框获得焦点
        document.getElementById('nicknameInput').focus();
    };
}, false);

document.getElementById('sendBtn').addEventListener('click', function() {
    var messageInput = document.getElementById('messageInput'),
        msg = messageInput.value,
        //获取颜色值
        color = document.getElementById('colorStyle').value;
        messageInput.value = '';
        messageInput.focus();
        if (msg.trim().length != 0) {
            //显示和发送时带上颜色值参数
            if($('#to').val() != ''){
                that.socket.emit('privateMessage', $('#from').val(), $('#to').val(),msg,color);//发送私聊
                that._displayNewMsg('我', msg, color, 0);
            }else{
                parent.layer.alert('请选择一个用户');
                // that.socket.emit('postMsg', msg, color);//发送群聊
            }
        }else if(msg.trim() != ' '){
            parent.layer.alert('发送内容不能为空');
        };
}, false);



