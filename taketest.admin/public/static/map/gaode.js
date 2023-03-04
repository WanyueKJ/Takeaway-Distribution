const Gaode={
    map:null
    ,init(data={},callback=null){
        let _this=this;
        AMapLoader.load({
            "key": gaode_key,              // 申请好的Web端开发者Key，首次调用 load 时必填
            "version": "2.0",   // 指定要加载的 JSAPI 的版本，缺省时默认为 1.4.15
            "plugins": [
                'AMap.Driving',
                'AMap.Riding',
            ], // 需要使用的的插件列表，如比例尺'AMap.Scale'等
            "AMapUI": {             // 是否加载 AMapUI，缺省不加载
                "version": '1.1',   // AMapUI 版本
                "plugins":['overlay/SimpleMarker'],       // 需要加载的 AMapUI ui插件
            },
            "Loca":{                // 是否加载 Loca， 缺省不加载
                "version": '2.0'  // Loca 版本
            },

        }).then((AMap)=>{

            _this.map = new AMap.Map('mapbd',{
                zoom:13, //初始化地图层级
            });

            //实时路况图层
            var trafficLayer = new AMap.TileLayer.Traffic({
                zIndex: 10
            });
            _this.map.add(trafficLayer);//添加图层到地图

            AMap.plugin([
                'AMap.ToolBar',
                'AMap.Scale',
            ], function(){
                // 在图面添加工具条控件，工具条控件集成了缩放、平移、定位等功能按钮在内的组合控件
                _this.map.addControl(new AMap.ToolBar());

                // 在图面添加比例尺控件，展示地图在当前层级和纬度下的比例尺
                _this.map.addControl(new AMap.Scale());

            });

            if (typeof callback == 'function') {
                callback.apply();
            }
        }).catch((e)=>{
            console.error(e);  //加载错误提示
        });

    }
    ,setCenter(lng,lat){
        let _this=this;
        return _this.map.setCenter([lng,lat]);
    }
    ,setZoom(zoom){
        /* zoom  3-20 */
        let _this=this;
        if(zoom<3 || zoom >20){
            return !1;
        }
        return _this.map.setZoom(zoom);
    }
    ,clear(){
        let _this=this;
        return _this.map.clearMap();
    }
    ,getMapBounds(){
        let _this=this;
        return _this.map.getBounds();
    }
    ,createIcon(data={}){
        let _this=this;
        let default_data={
            size: [25, 34],
            image: '//a.amap.com/jsapi_demos/static/demo-center/icons/dir-marker.png',
            imageSize: [135, 40],
            imageOffset: [-95, -3]
        };

        default_data=$.extend(default_data,data);

        return new AMap.Icon(default_data);
    }
    ,creatMarker(lng,lat,icon,data={}){
        let _this=this;
        let default_data={
            map: _this.map,
            position: [lng, lat],
            zIndex: 1,
            icon: icon,
            anchor : 'bottom-center',
        };

        default_data=$.extend(default_data,data);

        return new AMap.Marker(default_data);

    }
    ,addMarker(marker=[]){
        /* zoom  3-20 */
        let _this=this;
        if( marker.length<1 ){
            return !1;
        }
        return _this.map.add(marker);
    }

    ,creatLabelMarker(lng,lat,icon,text,data={}){
        let default_data={
            name: '标注2', // 此属性非绘制文字内容，仅最为标识使用
            position: [lng, lat],
            zIndex: 1,
            // 将第一步创建的 icon 对象传给 icon 属性
            icon: icon,
            // 将第二步创建的 text 对象传给 text 属性
            text: text,
        };

        default_data=$.extend(default_data,data);

        return new AMap.LabelMarker(default_data);

    }
    ,createLabels(){
        return new AMap.LabelsLayer({
            zooms: [3, 20],
            zIndex: 1000,
            // 该层内标注是否避让
            collision: true,
            // 设置 allowCollision：true，可以让标注避让用户的标注
            allowCollision: true,
        });
    }
    ,addLabels(LabelsLayer){
        let _this=this;
        return _this.map.add(LabelsLayer);
    }
    ,createDriving(data){
        let _this=this;
        let default_data={
            //驾车策略，包括 LEAST_TIME，LEAST_FEE, LEAST_DISTANCE,REAL_TRAFFIC
            policy: AMap.DrivingPolicy.LEAST_TIME,
            map: _this.map,
        };

        default_data=$.extend(default_data,data);

        return new AMap.Driving(default_data);
    }
    ,createRiding(data){
        let _this=this;
        let default_data={
            //策略，0：推荐路线及最快路线综合 1：推荐路线 2：最快路线
            policy: 0,
            // map 指定将路线规划方案绘制到对应的AMap.Map对象上
            map: _this.map,
        };

        default_data=$.extend(default_data,data);

        return new AMap.Riding(default_data);
    }


}