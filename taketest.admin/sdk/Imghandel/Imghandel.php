<?php  

class Imghandel
{
    private $error  = false;
    private $font   = __DIR__.'/msyhl.ttc';

    public function __construct($config=[])
    {
        $fonturl= $config['font'] ?? '';
        if($fonturl!=''){
            $this->font = $fonturl;
        }
    }

    public function getError()
    {
        return $this->error;
    }

    public function setFont($fonturl)
    {
        $this->font = $fonturl;
    }

    /* 获取字体 */
    public function getFont(){
        return $this->font;
    }
    
    /* 图片创建-图片链接-本地图片
        $url 图片链接 相对路径
        return 图片文本流
    */
    public function create($url){

        $img='';
        list($bgWidth, $bgHight, $bgType) = getimagesize($url);
        switch ($bgType) {
            case 1 :
                $img = @imagecreatefromgif($url);
                break;
            case 2 :
                $img = @imagecreatefromjpeg($url);
                break;
            case 3 :
                $img = @imagecreatefrompng($url);
                break;
            default:
                break;
        }
        return $img;
    }

    /* 图片创建-图片流
        $string 图片文本流
        return 图片文本流
    */
    public function createBystring($string){

        $img = @imagecreatefromstring($string);
        return $img;
    }

    /* 图片创建-图片链接-网络图片
        $url 图片链接 http开头链接
        return 图片文本流
    */
    public function createByHttp($url){
        $string=$this->curl_get($url);
        $img = @imagecreatefromstring($string);
        return $img;
    }

    /* 图片创建-图片流
        $img 图片文本流
    */
    public function getX($img){
        return @imagesx($img);
    }

    /* 图片创建-图片流
        $img 图片文本流
    */
    public function getY($img){
        return @imagesy($img);
    }
    /* 图片缩放
       $img 图片文本流
       $width 指定缩放宽度
       $height 指定缩放高度
       return 图片文本流
    */
    public function zoom($img,$width,$height){

        $new=@imagecreatetruecolor($width, $height);
        $width_old = @imagesx($img);
        $height_old = @imagesy($img);
        //copy部分图像并调整
        @imagecopyresized($new, $img,0, 0,0, 0,$width, $height, $width_old, $height_old);

        return $new;
    }

    /* 图片切圆角
        $img 图片文本流
        return 图片文本流
    */
    public function fillet($img){

        $w = @imagesx($img);
        $h = @imagesy($img);
        $c = @imagecolorallocate($img, 255, 0, 0);
        @imagearc($img, $w/2, $h/2, $w, $h, 0, 360, $c);
        @imagefilltoborder($img, 0, 0, $c, $c);
        @imagefilltoborder($img, $w, 0, $c, $c);
        @imagefilltoborder($img, 0, $h, $c, $c);
        @imagefilltoborder($img, $w, $h, $c, $c);

        @imagecolortransparent($img, $c); //!!!!

        return $img;
    }

    /* 图片合并
        $bgimg 背景图片文本流
        $img   合并图片文本流
        $left  合并图片X位置
        $top   合并图片Y位置
        return 合并后的图片文本流
    */
    public function merge($bgimg,$img,$left,$top){

        $img_w = @imagesx($img);
        $img_h = @imagesy($img);

        @imagecopymerge($bgimg, $img, $left, $top, 0, 0, $img_w, $img_h, 100);

        return $bgimg;
    }

    /* 获取文字宽高
        $text 文字内容
        $font_size  字号
        return：
            width  文字生成图片的宽度
            height 文字生成图片的高度
    */
    public function getStringSize( $text, $font_size) {
        $font_file = $this->font;  //字体文件
        $font_width = @ImageFontWidth($font_size);
        $font_height = @ImageFontHeight($font_size);

        $string = mb_convert_encoding($text, 'html-entities', 'UTF-8');
        //取得 str 2 img 后的宽度
        $temp = @imagecreatetruecolor($font_height, $font_width);

        $res = @imagefttext($temp, $font_size, 0, 0, 0, 1, $font_file, $string);

        $strImgWidth = $res[2] - $res[0];
        $strImgHeight = $res[1] - $res[7];
        return [
            'width'=>$strImgWidth,
            'height'=>$strImgHeight,
        ];
    }

    /* 图片添加文字 */
    public function addstring($bgimg,$font_size,$angle,$left,$top,$color,$str,$weight=1){

        $font = $this->font;
        $text_color = @imagecolorallocate($bgimg, $color[0], $color[1], $color[2]);//字体颜色 RGB

        for ($i=0; $i<$weight; $i++) {
            @imagefttext($bgimg, $font_size, $angle, $left, $top, $text_color, $font, $str);
            $left++;
            $top++;
        }

        return $bgimg;
    }

    /* 图片添加文字-水平居中 */
    public function addstringByCenter($bgimg,$font_size,$angle,$top,$color,$str,$weight=1){

        $str_size=$this->getStringSize($str,$font_size);
        $left=(@imagesx($bgimg) - $str_size['width'] ) / 2;
        $top=$top + $str_size['height'];

        return $this->addstring($bgimg,$font_size,$angle,$left,$top,$color,$str,$weight);
    }

    /* 生成图片
        $type 1jif 2jpg 3png
    */
    public function generate($bgimg,$path,$type=0){

        @imagesavealpha($bgimg, true);

        switch ($type) {
            case 1: //gif
                @imagegif($bgimg,$path);
                break;
            case 2: //jpg
                @imagejpeg($bgimg,$path);
                break;
            case 3: //png
                @imagepng($bgimg,$path);
                break;
            default:
                @imagepng($bgimg,$path);
                break;
        }
        return 1;
    }

    /* curl get请求 */
    private function curl_get($url){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_NOBODY, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 跳过证书检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);  // 从证书中检查SSL加密算法是否存在
        $return_str = curl_exec($curl);
        curl_close($curl);
        return $return_str;
    }

}