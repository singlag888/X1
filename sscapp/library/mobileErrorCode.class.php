<?php
/* * ****************************************************
 * FILE      : 用于errorCode静态常量类
 * @copyright: 金亚洲开发部
 * @Describe : APP系统所有错误都定义于此
 * **************************************************** */

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 用于转盘活动， 排奖计划与中奖派奖逻辑
 * @author Davy
 */
class mobileErrorCode
{
    /**
     * 全APP正常返回的code标识
     */
    const RETURN_SUCCESS = "执行成功";

    /**
     * 6001 逻辑异常之外的报错统一给客户端返回网络错误
     */
    const NET_ENV_EXCEPTION = '网络不稳定请稍后再试';
    /**
     * 6002 请求方式错误
     */
    const REQUEST_ERROR = '请求方式错误';
    /**
     * 6003 请求参数错误
     */
    const REQUEST_PARAMS_ERROR = '请求参数错误';
    /**
     * 6004 访问域名错误
     */
    const REQUEST_HOST_ERROR = '访问域名不在总代域名下';
    /**
     * 6005 系统出错(多为出现脏数据或错误数据终端程序运行)
     */
    const SYS_ERROR = '系统出错';
    /**
     * 6006 reids错误
     */
    const REDIS_ERROR = 'redis出错';
    /**
     * 6007 config配置错误
     */
    const CONFIG_ERROR = '配置错误';
    /**
     * 6008 math错误
     */
    const MATH_ERROR = '计算错误';
    /**
     * 6009 系统维护
     */
    const SYSTEM_MAINTENANCE = '系统维护';
    /******************************************登陆错误**********************************************/
    /**
     * 7001
     */
    const USER_NOT_LOGIN = '请您先登录再访问';

    /**
     * 7002
     */
    const USER_NOT_HAVE_ACL = '抱歉!您暂时没有权限访问';

    /**
     * 7003
     */
    const MENU_NOT_FOUND = '访问的菜单不存在';

    /**
     * 7004
     */
    const USER_NAME_OR_PWD_NULL = '用户名或密码为空';

    /**
     * 7005
     */
    const USER_LOGIN_ERR = '登录中出错';

    /**
     * 7006
     */
    const USER_LOGIN_OTHER_SIDE = "您已经从别处登录，如果这不是您本人亲自操作，为保证安全请立即修改密码！";

    /**
     * 7007 提款统一错误定义，,具体errstr内容可根据情况自定义
     */
    const USER_WITHDRAW_TRANSACTION_ERR = "提款处理错误";

    /**
     * 7008
     */
    const USER_NOT_MONEY_PWD = "您尚未设置资金密码，请先设置资金密码";

    /**
     * 7009
     */
    const USER_NOT_BIND_CARD = "您尚未绑定任何银行卡，请先绑定卡号方可提款";

    /**
     * 7010
     */
    const USER_WITHDRAW_LIMIT = "你已经超过每天提现次数限制！";

    /**
     * 7011
     */
    const USER_INPUT_ERR = "请填写详细正确的信息！";

    /**
     * 7012
     */
    const USER_INVALID = "不合法或无效用户！";

    /**
     * 7013
     */
    const USER_REBATES_ERR = "返点错误！";

    /**
     * 7014
     */
    const USER_INVALID_ACCESS = "非法访问";

    /**
     * 7015
     */
    const USER_DUPLICATE_ORDER = "重复的订单";

    /**
     * 7016
     */
    const USER_TRACE_NUM = "追号期数不正确";

    /**
     * 7017
     */
    const USER_NONE_TRACE_NUM = "没有可追号奖期";

    /**
     * 7018
     */
    const CHILDREN_NOT_FOUND = "此用户不是你的下级";
    /**
     * 绑定银行卡
     * 7020
     */
    /**
     * 提现ip记录 错误
     * 7021
     */
    const WITHDRAWIP_ADD_ERROR = '保存提款外镜ip失败';
    /**
     * 充值明细 错误
     * 7022
     */
    /**
     * 团队盈亏 错误
     * 7023
     */
    /**
     * 会员管理 错误
     * 7024
     */
    /**
     * 推广码 错误
     * 7025
     */
    /**
     * 我的订单 错误
     * 7026
     */

    /**
     * 帮助页面 错误
     * 7027
     */

    /**
     * 意见反馈 错误
     * 7028
     */
    const LOTTERY_NOT_EXISTS = "彩种不存在";//7029
    const ISSUES_DATA_ERROR = '奖期数据错误';//7030
    /**
     * 7031 此彩种您还没有分配返点，请联系上级或者客服
     */
    /**
     * 7032 apitoken错误
     */
    /**
     * 7033 获取启动页和欢迎页错误
     */
    /**
     * 7034 验证码错误
     */
    const VERIFYCODE_ERROR = '验证码错误';
    /**
     * 7035 获取启动页和欢迎页错误
     */
    /**
     * 7036 修改登录密码
     */

    /**
     * 8001
     */
    const GET_DATA_FAIL = '查询失败';

    /**
     * 8002    API参数错误
     */
    const SYS_GET_PARAM_ERR = 'API参数错误';

    /**
     * 8003
     */
    const PARAM_TYPE_UNKNOW = '未知类型数据';

    /**
     * 9001 插入,更新,删除失败,具体errstr内容可根据情况自定义
     */
    const MYSQL_DML_FAIL = '操作失败';
    /**************************************************注册问题都以5开头******************************************************/
    /**
     * 注册问题都以5开头
     * 5001
     */
    const USERNAME_FORMAT_ERROR = '用户名长度为6-12个字母或数字，必须以字母开头';
    /**
     * 注册问题都以5开头
     * 5002
     */
    const REALNAME_FORMAT_ERROR = '真实姓名必须为中文';
    /**
     * 注册问题都以5开头
     * 5003
     */
    const PWD_FORMAT_ERROR = '密码长度为6-15位字母数字混合，不能为纯数字或纯字母';
    /**
     * 注册问题都以5开头
     * 5004
     */
    const QQ_FORMAT_ERROR = 'qq号码有误';
    /**
     * 注册问题都以5开头
     * 5005
     */
    const TEL_FORMAT_ERROR = '手机号码有误';
    /**
     * 注册问题都以5开头
     * 5006
     */
    const REALNAME_REPEAT_ERROR = '该姓名已被注册';
    /**
     * 注册问题都以5开头
     * 5007
     */
    const USERNAME_REPEAT_ERROR = '注册账号已存在';
    /**
     * 注册问题都以5开头
     * 5008
     */
    const PROMO_CODE_ERROR = '对不起，该上级推广链接不正确';
    /**
     * 注册问题都以5开头
     * 5009
     */
    const REGISTER_ERROR = '注册用户失败!请检查数据输入是否完整';
    /**
     * 注册问题都以5开头
     * 5010
     */
    const MOBILE_REPEAT_ERROR = '该手机号已被注册';
    /**
     * 注册问题都以5开头
     * 5011
     */
    const QQ_REPEAT_ERROR = '该QQ号已被注册';


    /**
     * 5012
     */
    const SECPWD_FORMAT_ERROR = "'资金密码长度为6-16位字母数字混合，不能为纯数字或纯字母";
    /**
     * 5013
     */
    const SECPWD_SAME = "资金密码与之前相同";
    /**
     * 5014
     */
    const SECPWD_UPDATE_ERROR = "修改资金密码失败";
    /**
     * 5015
     */
    const SECPWD_SAME_ERROR = "资金密码不能与登录密码相同";

    /**
     * 5015
     */
    const SECPWD_EXISTS = "资金密码已设置,修改请联系客服";
    const UPDATE_USER_INFO_ERROR = "设置用户信息失败,请稍后再试!";//5016
    const UPDATE_USER_SAME_ERROR = "用户相同数据无需修改";//5017
    const UPDATE_USERPWD_SAME_ERROR = "登录密码与原密码相同,无法修改!";//5018

    const PAY_SHOW_ERROR = "展示充值页面失败,支付数据错误";//3001

    const DATA_EXCEPTION = "数据异常,请稍后再试";//4004

    /**
     * 8开头彩种相关
     */


}

?>