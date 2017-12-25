/**
 * Created by zhangyuhan 2017/7/18.
 */
/*productPage*/
function addListClick(){
  /*改变样式*/
  $(".toggle-label span").on('touchend',function () {
    $(this).addClass('toggle-check').siblings().removeClass("toggle-check")
  })
  /*改变数量*/
  $("span[name='reduction']").on('touchend',function () {
    /*设置最小值*/
    var tempNumber = parseInt($(".myinput-value").text())
    var tempNum = tempNumber > 1? tempNumber-1: tempNumber
    $(".myinput-value").text(tempNum)
  })
  $("span[name='add']").on('touchend',function () {
    $(".myinput-value").text(parseInt($(".myinput-value").text()) + 1)
  })
}
addListClick()
/*--end--*/