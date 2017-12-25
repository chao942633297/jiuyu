/**
 * Created by zhangyuhan on 2017/7/19.
 */
/*pop使用需知
 * 需要使用symbol方式引用 font/iconfont.js
 * 需要jQuery
 * 使用方法 ——>模拟Data数据,调用函数createPop(Data)即可
 * */
/*
 数据样式
 const testData = {
 title:'我是标题',
 content:'你还不是金牌会员，预存1000元升级为 金牌会员获得更多权益！！！',
 button:'我是按钮'
 }*/
/*创建Pop*/
"use strict";

function createPop(data, callback) {
  /*生产模板*/
    function createTempLate(_ref) {
        var title = _ref.title;
        var content = _ref.content;
        var button = _ref.button;

        return "\n      <my-pop-bg>\n    <my-pop>\n        <pop-title>\n            <div class=\"close-sm\" onclick='hidePop()'><svg class=\"icon close-ico\" aria-hidden=\"true\">\n                <use xlink:href=\"#icon-guanbigongjulan\"></use>\n            </svg></div>\n            <span>" + title + "</span>\n        </pop-title>\n        <pop-content>" + content + "</pop-content>\n        <pop-button><button>" + button + "</button></pop-button>\n    </my-pop>\n    </my-pop-bg>\n      ";
    }
  /*禁止滚动*/
    $("body").css("overflow", "hidden").append(createTempLate(data));
  /*点击事件*/
    $("body").on('click touchend', '.close-sm', function () {
        hidePop();
    });
    $("body").on('click touchend', 'pop-button', callback);
}
/*销毁Pop*/
function hidePop() {
    $("body").css("overflow", "auto");
    $("my-pop-bg").addClass("pop-hide");
    $("my-pop").addClass('pop-up');
    setTimeout(function () {
        $("my-pop-bg").remove();
    }, 500);
}