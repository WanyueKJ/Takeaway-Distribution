
const Rider={
    listbd:$('.js_riderlist')
    ,searchBtn:$('.js_rider_search_btn')
    ,keyword:$('#js_searchbd_rider')
    ,markers:[]
    ,init(){
        let _this=this;
        _this.getList();

        _this.listbd.on('click','.rider_item_btn',function (){
            let _that=$(this);
            let seclected=Order.getSelected();

            if(!seclected){
                return !1;
            }
            let seclected_obj=$(seclected);
            let oid=seclected_obj.data('id');

            console.log(oid);

            let rid=_that.parents('.rider_item').data('id');
            let res=_this.designate(rid,oid);
            if(!res){
                return !1;
            }
            seclected_obj.remove();
            Order.clearPlan();
        })

        _this.searchBtn.on('click',function (){
            let keyword=_this.keyword.val();
            /*if(keyword==''){
                layer.msg('请输入骑手姓名');
                return !1;
            }*/
            _this.getList();
        })

    }
    ,getList() {
        let _this=this;

        $.ajax({
            url: '/substation/rider/getList',
            type: 'POST',
            data: {keyword:_this.keyword.val()},
            dataType: 'json',
            error(e) {
                layer.msg('网络错误');
                _this.isscroll = true;
            },
            success(data) {
                if (data.code == 0) {
                    layer.msg(data.msg);
                    return !1;
                }

                let list = data.data;
                let nums = list.length;
                if (nums == 0) {
                    return !1;
                }

                let html = '';
                for (let i = 0; i < nums; i++) {
                    html += _this.handleItem(list[i]);
                }

                _this.listbd.html(html);

                _this.setRiderMap(list);

            }
        })
    }
    ,handleItem(data){
        let _this=this;
        let html='<div class="rider_item" data-id="'+data.id+'">\n' +
            '                        <div class="rider_item_t">\n' +
            '                            <div class="rider_item_n">'+data.user_nickname+'</div>\n' +
            '                            <div class="rider_item_nums">已接单：'+data.orders+'</div>\n' +
            '                        </div>\n' +
            '                        <div class="rider_item_c">\n' +
            '                            <div class="rider_item_p">'+data.mobile+'</div>\n' +
            '                        </div>\n' +
            '                        <div class="rider_item_b">\n' +
            '                            <span class="rider_item_btn">指派</span>\n' +
            '                        </div>\n' +
            '                    </div>';

        return html;
    }
    ,setRiderMap(list){

        let _this=this;
        _this.clearMarker();
        let icon_data = {
            size: [30, 30],
            image: '//vdata.amap.com/style_icon/2.0/icon-normal-big.png',
            imageSize: [384, 768],
            imageOffset: [-60,-329]
        };

        let icon = Gaode.createIcon(icon_data);

        for(let i=0;i<list.length;i++){
            let v=list[i];
            let lng=v.lng;
            let lat=v.lat;
            if(lng!='' && lat!=''){
                let data = {
                    label:{
                        content :'<div class="map_name">'+v.user_nickname+'</div>'
                        ,direction :'top'
                    }
                };

                let marker = Gaode.creatMarker(lng,lat,icon,data);

                _this.markers.push(marker);
            }

        }

        Gaode.addMarker(_this.markers);
    }
    ,clearMarker(){
        let _this=this;
        for (let i=0;i<_this.markers.length;i++){
            _this.markers[i].remove();
        }
        _this.markers=[];
    }
    ,designate(rid,oid){
        let _this=this;
        let isok=false;
        $.ajax({
            url: '/substation/orders/designate',
            type: 'POST',
            async:false,
            cache:false,
            data: {rid:rid,oid:oid},
            dataType: 'json',
            error(e) {
                layer.msg('网络错误');
                _this.isscroll = true;
            },
            success(data) {
                if (data.code == 0) {
                    layer.msg(data.msg);
                    return !1;
                }
                layer.msg(data.msg);
                isok=true;
            }
        })

        return isok;
    }
};
const Order={
    listbd:$('.js_orderlist')
    ,searchBtn:$('.js_order_search_btn')
    ,keyword:$('#js_searchbd_order')
    ,page:1
    ,isscroll:true
    ,riding:null
    ,driving:null
    ,endmarker:null
    ,init(){
        let _this=this;
        _this.getList();

        let scroll_obj=_this.listbd;
        scroll_obj.scroll(function(){
            var scrollHeight = scroll_obj.prop('scrollHeight');    //总内容高度
            var srollPos = scroll_obj.scrollTop();    //滚动条距顶部距离(页面超出窗口的高度)
            var totalheight = parseFloat(scroll_obj.height()) + parseFloat(srollPos);

            if((scrollHeight-50) <= totalheight ) {
                _this.getList();
            }
        });

        _this.listbd.on('click','.order_item',function (){
            let _that=$(this);
            if(_that.hasClass('on')){
                return !1;
            }
            let id=_that.data('id');
            let f_name=_that.data('f_name');
            let f_lat=_that.data('f_lat');
            let f_lng=_that.data('f_lng');
            let t_name=_that.data('t_name');
            let t_lat=_that.data('t_lat');
            let t_lng=_that.data('t_lng');

            _that.siblings().removeClass('on');
            _that.addClass('on');

            _this.plan(2,f_lng,f_lat,t_lng,t_lat);
        })

        _this.searchBtn.on('click',function (){
            let keyword=_this.keyword.val();
            /*if(keyword==''){
                layer.msg('请输入订单号');
                return !1;
            }*/
            _this.resetPage();
            _this.getList();
        })
    }
    ,resetPage(){
        let _this=this;
        _this.page=1;
        _this.isscroll=true;
    }
    ,getList(){
        let _this=this;
        if(!_this.isscroll){
            return !1;
        }
        _this.isscroll=false;
        $.ajax({
            url:'/substation/orders/getList',
            type:'POST',
            data:{status:2,page:_this.page,keyword:_this.keyword.val()},
            dataType:'json',
            error(e){
                layer.msg('网络错误');
                _this.isscroll=true;
            },
            success(data){
                if(data.code==0){
                    layer.msg(data.msg);
                    return !1;
                }

                let list=data.data.data;
                let nums=list.length;
                if(nums==0){
                    return !1;
                }

                let html='';
                for(let i=0;i<nums;i++){
                    html+=_this.handleItem(list[i]);
                }
                if(_this.page==1){
                    _this.listbd.html(html);
                }else{
                    _this.listbd.append(html);
                }

                _this.isscroll=true;
                _this.page++;

            }

        });
    }
    ,handleItem(data){
        let _this=this;
        let type=data.type;
        let html='<div class="order_item" data-id="'+data.id+'" data-type="'+data.type+'" data-f_name="'+data.f_name+'" data-f_lat="'+data.f_lat+'" data-f_lng="'+data.f_lng+'" data-t_name="'+data.t_name+'" data-t_lat="'+data.t_lat+'" data-t_lng="'+data.t_lng+'">\n' +
            '                        <div class="order_item_type">\n' +
            '                            <span class="order_item_typetxt">'+data.type_t+'</span>\n' +
            '                            <span class="order_item_sel layui-icon layui-icon-ok"></span>\n' +
            '                        </div>\n' +
            '                        <div class="order_item_no">\n' +
            '                            订单号:'+data.orderno+'\n' +
            '                        </div>\n';
            if(type==1 || type==2){
                html+= '                        <div class="order_item_location">\n' +
                    '                            <span class="order_item_tag">取</span>\n' +
                    '                            <span class="order_item_name">'+data.f_name+'</span>\n' +
                    '                        </div>\n' +
                    '                        <div class="order_item_location">\n' +
                    '                            <span class="order_item_tag on">收</span>\n' +
                    '                            <span class="order_item_name">'+data.t_name+'</span>\n' +
                    '                        </div>\n';
            }
            if(type==3){
                if(data.extra.type==2){
                    data.f_name='就近';
                }

                html+= '                        <div class="order_item_location">\n' +
                    '                            <span class="order_item_tag">买</span>\n' +
                    '                            <span class="order_item_name">'+data.f_name+'</span>\n' +
                    '                        </div>\n';

                html+='                        <div class="order_item_location">\n' +
                    '                            <span class="order_item_tag on">收</span>\n' +
                    '                            <span class="order_item_name">'+data.t_name+'</span>\n' +
                    '                        </div>\n';
            }

            if(type==4 || type==5){
                html+='                        <div class="order_item_location">\n' +
                    '                            <span class="order_item_tag on">服</span>\n' +
                    '                            <span class="order_item_name">'+data.t_name+'</span>\n' +
                    '                        </div>\n';
            }


            html+='                        <div class="order_item_user">\n' +
            '                            下单用户：'+data.uinfo.user_nickname+'\n' +
            '                        </div>\n' +
            '                        <div class="order_item_service">\n' +
            '                            预约时间：'+data.service_time+'\n' +
            '                        </div>\n' +
            '                        <div class="order_item_add">\n' +
            '                            下单时间:'+data.add_time+'\n' +
            '                        </div>\n' +
            '                    </div>';

        return html;
    }
    ,getSelected(){
        let _this=this;

        let selecteds=_this.listbd.find('.order_item.on');
        if(selecteds.length<1){
            layer.msg('请先选择订单');
            return !1;
        }

        return selecteds[0];
    }
    ,plan(type,f_lng,f_lat,t_lng,t_lat){

        let _this=this;
        _this.clearPlan();

        if(f_lng=='' || f_lat==''){
            if(t_lng!='' && t_lat!=''){
                _this.endMarker(t_lng,t_lat);
            }
            return !1;
        }

        if(type==1){
            _this.Driving(f_lng,f_lat,t_lng,t_lat);
        }
        if(type==2){
            _this.Riding(f_lng,f_lat,t_lng,t_lat);
        }
    }
    ,clearPlan(){
        let _this=this;
        if(_this.driving){
            _this.driving.clear();
        }
        if(_this.riding){
            _this.riding.clear();
        }

        if(_this.endmarker){
            _this.endmarker.remove();
            _this.endmarker=null;
        }

    }
    ,Driving(f_lng,f_lat,t_lng,t_lat){
        let _this=this;
        if(!_this.driving){
            _this.driving = Gaode.createDriving();
        }

        let startLngLat = [f_lng, f_lat]
        let endLngLat = [t_lng, t_lat]

        _this.driving.search(startLngLat, endLngLat, function (status, result) {
            // 未出错时，result即是对应的路线规划方案
        })
    }
    ,Riding(f_lng,f_lat,t_lng,t_lat){
        let _this=this;

        if(!_this.riding){
            _this.riding = Gaode.createRiding();
        }

        let startLngLat = [f_lng, f_lat]
        let endLngLat = [t_lng, t_lat]

        _this.riding.search(startLngLat, endLngLat, function (status, result) {
            // 未出错时，result即是对应的路线规划方案
            //console.log('Riding');
            //console.log(status);
            //console.log(result);
        })

    }
    /* 规划路线-仅有目的地 */
    ,endMarker(lng,lat){

        let _this=this;
        let icon = Gaode.createIcon();
        _this.endmarker = Gaode.creatMarker(lng,lat,icon);

        Gaode.addMarker([_this.endmarker]);
        Gaode.setCenter(lng,lat);
        Gaode.setZoom(18);

    }

};

(function (){
    Gaode.init({},function (){
        Order.init();
        Rider.init();
    });
})()