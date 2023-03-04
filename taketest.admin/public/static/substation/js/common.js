Wind.css('layer');
Wind.use('layer');

function handelRes(data){
    if(data.url!=''){
        layer.msg(data.msg,{},function(){
            location.href=data.url;
        });
    }else{
        layer.msg(data.msg);
    }
}

/* 处理时长 */
function handelTime(time){
    var s=time%60;
    var i_t=Math.floor(time/60);
    var i=i_t%60;
    var h_t=Math.floor(i_t/60);
    var h=h_t%24;
    var d=Math.floor(h_t/24);

    if(s<10){
        s='0'+s;
    }
    if(i<10){
        i='0'+i;
    }
    if(h<10){
        h='0'+h;
    }
    var data={
        d:d,
        h:h,
        i:i,
        s:s
    };
    return data;
}

function layerimg(src){
    let html='<div class="layer_img"><img src="'+src+'"></div>';
    layer.open({
        type: 1,
        title: false,
        closeBtn: 0,
        resize:false,
        area: ['90%','90%'],
        shadeClose: true,
        content: html
    });
}
function cloneObject(obj){
    return JSON.parse(JSON.stringify(obj))
}

/*判断浏览器版本**/
const checkBrowser={
    myBrowser:function(){
        var isPlay=true;
        var userAgent = navigator.userAgent; //取得浏览器的userAgent字符串
        if (userAgent.indexOf("Chrome") > -1){ //谷歌浏览器
            var arr = navigator.userAgent.split(' ');
            var chromeVersion = '';
            for(var i=0;i < arr.length;i++){
                if(/chrome/i.test(arr[i]))
                    chromeVersion = arr[i]
            }
            if(chromeVersion){
                if(Number(chromeVersion.split('/')[1].split('.')[0])>70){
                    isPlay=false;
                }
            }
        }
        if (userAgent.indexOf("Safari") > -1) {
            isPlay=false;
        }
        return isPlay;
    },
    isChrome:function(){
        var isPlay=0;
        var userAgent = navigator.userAgent; //取得浏览器的userAgent字符串
        if (userAgent.indexOf("Chrome") > -1){ //谷歌浏览器
            var arr = navigator.userAgent.split(' ');
            var chromeVersion = '';
            for(var i=0;i < arr.length;i++){
                if(/chrome/i.test(arr[i]))
                    chromeVersion = arr[i]
            }
            if(chromeVersion){
                if(Number(chromeVersion.split('/')[1].split('.')[0])>72){
                    isPlay=1;
                }else{
                    isPlay=-1;
                }
            }
        }

        return isPlay;
    }
}
$(function(){

})