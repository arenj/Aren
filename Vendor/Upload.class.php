<?php
namespace Aren\Vendor;


use Aren\Core\File;

class Upload
{
    /**
     * 上传文件
     */
    private $_files = array();
    /**
     * 上传文件数量
     */
    private $_count = 0;

    private $_flash = false;

    private $_single = true;

    private $_config = array();

    /**
     * 构造函数
     * @param array $config
     */
    function __construct($config = array())
    {
        if (is_array($_FILES)) {
            foreach ($_FILES as $field => $struct) {
                if (isset ($struct['error']) && ($struct['error'] === UPLOAD_ERR_OK) && ($struct['tmp_name'] != 'none') && (is_uploaded_file($struct['tmp_name']) || is_uploaded_file(str_replace('\\\\', '\\', $struct['tmp_name'])))) {
                    $struct['ext'] = $this->Fext($struct['name']);
                    $this->_files[$field] = $struct;
                }
            }
            $this->_count = count($this->_files);
        }
        $this->_config = $config;
        $this->_flash = false;
        $this->_single = true;
    }

    /**
     * 返回文件对象
     */
    function getFiles()
    {
        return $this->_files;
    }

    /**
     * 取得上传文件数量
     */
    function getCount()
    {
        return $this->_count;
    }

    /**
     * 返回上传文件属性
     * @param $item
     * @param $att
     * @return null
     */
    function getAttribut($item, $att)
    {
        return isset($this->_files[$item][$att]) ? $this->_files[$item][$att] : NULL;
    }

    /**
     * 设置上传文件属性
     * @param $item
     * @param $att
     * @param $value
     */
    function setAttribut($item, $att, $value)
    {
        if (isset($this->_files[$item])) {
            $this->_files[$item][$att] = $value;
        }
    }

    /**
     * 返回浏览器提供的文件类型
     * @param $fileext
     * @return string
     */
    function getMimeType($fileext)
    {
        switch ($fileext) {
            case 'pdf' :
                $mimetype = 'application/pdf';
                break;
            case 'rar' :
            case 'zip' :
                $mimetype = 'application/zip';
                break;
            case 'doc' :
                $mimetype = 'application/msword';
                break;
            case 'xls' :
                $mimetype = 'application/vnd.ms-excel';
                break;
            case 'ppt' :
                $mimetype = 'application/vnd.ms-powerpoint';
                break;
            case 'gif' :
                $mimetype = 'image/gif';
                break;
            case 'png' :
                $mimetype = 'image/png';
                break;
            case 'jpeg' :
            case 'jpg' :
                $mimetype = 'image/jpeg';
                break;
            case 'wav' :
                $mimetype = 'audio/x-wav';
                break;
            case 'mpeg' :
            case 'mpg' :
            case 'mpe' :
                $mimetype = 'video/x-mpeg';
                break;
            case 'mov' :
                $mimetype = 'video/quicktime';
                break;
            case 'avi' :
                $mimetype = 'video/x-msvideo';
                break;
            case 'txt' :
                $mimetype = 'text/plain';
                break;
            default :
                $mimetype = 'application/octet-stream';
        }
        return $mimetype;
    }

    /**
     * 返回上传是否为图片
     * @param $ext
     * @return int
     */
    function isImg($ext)
    {
        $ext = strtolower($ext);
        if (in_array($ext, array('gif', 'png', 'jpg', 'jpeg'))) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * 生产唯一文件名
     * @param $item
     * @return mixed
     */
    function makeFileName($item)
    {
        return preg_replace("~(php|phtml|php3|php4|jsp|exe|dll|asp|cer|asa|shtml|shtm|aspx|asax|cgi|fcgi|pl)~i", "_\\1_", md5($item['name'] . $item['size'] . $item['tmp_name'] . time()));
    }

    /**
     * 检查上传文件类型和大小是否符合要求
     *
     * @param $item
     * @param $type array 上传文件类型数组
     * @param Int $maxSize 文件最大尺寸，单位字节
     */
    function checkFile($item, $type = array(), $maxSize = 0)
    {
        if (is_array($type)) {
            $ck = FALSE;
            foreach ($type as $ext) {
                if ($item['ext'] == strtolower($ext)) {
                    $ck = TRUE;
                    break;
                }
            }
            if (!$ck) {
                $this->error('attachment_ext_notallowed');
            }
        }
        if ((int)$item['size'] < 1 || ($maxSize && $item['size'] > $maxSize)) {
            $this->error('attachment_size_invalid');
        }
    }

    function error($tip)
    {
        if ($this->_flash) {
            echo $tip;
        } else {
            $this->ajaxReturn($tip);
        }
    }

    function ajaxReturn($msg, $status = 0, $data = array())
    {
        header('Content-type:text/json');
        echo json_encode(array(
            'status' => $status,
            'info' => $msg,
            'data' => $data
        ));
        exit;
    }

    function ajaxJson($err = '', $msg = '')
    {
        header('Content-type:text/json');
        echo json_encode(array(
            'err' => $err,
            'msg' => $msg
        ));
        exit;
    }

    function Fext($filename)
    {
        return strtolower(trim(substr(strrchr($filename, '.'), 1)));
    }

    /**
     * 移动文件
     *
     * @param String $type 附件类型
     * @param String $filename 自定义文件名称
     * @param array $allowedExt
     * @param int $maxSize
     * @return array
     */
    function moveFile($type = 'web', $filename = '', $allowedExt = array('jpg', 'jpeg', 'gif', 'png'), $maxSize = 0)
    {

        $imgext = array('jpg', 'jpeg', 'gif', 'png');
        $_movedfile = $_imginfo = array();
        if($allowedExt || $maxSize){
            $check = true;
        }else{
            $check = false;
        }

        switch ($type) {
            case 'web':
                $_attpath = 'doc/web/' . date('Ymd', time()) . '/';
                break;
            case 'avatar':
                $_attpath = 'doc/avatar/' . date('Ymd', time()) . '/';
                break;
            default:
                $_attpath = 'doc/temp/' . date('Ymd', time()) . '/';
                break;
        }
        $abs_path = ROOT_PATH . $_attpath;
        if (!is_dir($abs_path)) {
            if (!@mkdir($abs_path, 0777) || !@fclose(@fopen($abs_path . 'index.html', 'w'))) $this->error('attachment_mkdir_failed');
        }

        foreach ($this->_files as $k => $v) {
            $check && $this->checkFile($v, $allowedExt, $maxSize);
            $moved = $_imginfo = '';
            !$filename && $filename = $this->makeFileName($v);

            $real_path = $abs_path . $filename . '.' . $v['ext'];
            if (move_uploaded_file($v['tmp_name'], $real_path) || @copy($v['tmp_name'], $real_path) || File::writeFile($real_path, File::readFile($v['tmp_name']), 'wb')) {
                $moved = true;
                File::delete($v['tmp_name']);
            }

            $isimg = $this->isImg($v['ext']);
            if ($moved) {
                @chmod($real_path, 0644);
                $_imginfo = getimagesize($real_path);
                if ($isimg && function_exists('getimagesize') && !$_imginfo) {
                    File::delete($real_path);
                    $this->error('attachment_illegal_image');
                }
                //判断是否需要裁剪

                //判断是否生成缩略图

                $_movedfile[] = array('attpath' => $_attpath, 'filename' => $filename, 'ext' => $v['ext'], 'size' => $v['size'], 'mime' => $this->getMimeType($v['ext']), 'thumb' => (isset($_thumb) ? 1 : 0), 'isimg' => $isimg, 'imginfo' => $_imginfo, 'realname' => HConvert($v['name']));
            } else {
                $this->error('attachment_save_error');
            }
            if ($this->_single) {
                break;
            }
        }
        //var_dump($_movedfile);
        return $_movedfile;
    }

    function cut($image, $maxWidth, $maxHeight)
    {
        return $this->thumb($image, $maxWidth, $maxHeight, '');
    }

    //生成缩略图
    function thumb($image, $maxWidth = 200, $maxHeight = 150, $save_prefix = 's_', $del = false, $interlace = true)
    {
        // 获取原图信息
        if (!$maxWidth) {
            $maxWidth = 200;
        }
        if (!$maxHeight) {
            $maxHeight = 150;
        }
        $info = $this->getImageInfo($image);
        if ($info !== false) {
            $srcWidth = $info['width'];
            $srcHeight = $info['height'];
            $type = strtolower($info['type']);
            $interlace = $interlace ? 1 : 0;
            unset($info);
            if ($save_prefix) {
                $imgpath = substr($image, 0, strrpos($image, '/'));
                $filename = substr($image, strrpos($image, '/') + 1);
                $savepath = $imgpath . '/' . $save_prefix . $filename;
            } else {
                $savepath = $image;
            }
            $scale = min($maxWidth / $srcWidth, $maxHeight / $srcHeight); // 计算缩放比例
            if ($scale >= 1) {  // 超过原图大小不再缩略
                $width = $srcWidth;
                $height = $srcHeight;
                if ($save_prefix) {
                    @copy($image, $savepath) || File::writeFile($savepath, File::readFile($image), 'wb');
                    $del && File::delete($image);
                }
                return array($savepath, $width, $height);
            } else {  // 缩略图尺寸
                $width = (int)($srcWidth * $scale);
                $height = (int)($srcHeight * $scale);
            }

            // 载入原图
            $createFun = 'ImageCreateFrom' . ($type == 'jpg' ? 'jpeg' : $type);
            $srcImg = $createFun($image);

            //创建缩略图
            if ($type != 'gif' && function_exists('imagecreatetruecolor')) {
                $thumbImg = imagecreatetruecolor($width, $height);
            } else {
                $thumbImg = imagecreate($width, $height);
            }
            // 复制图片
            if (function_exists("ImageCopyResampled")) {
                imagecopyresampled($thumbImg, $srcImg, 0, 0, 0, 0, $width, $height, $srcWidth, $srcHeight);
            } else {
                imagecopyresized($thumbImg, $srcImg, 0, 0, 0, 0, $width, $height, $srcWidth, $srcHeight);
            }
            if ('gif' == $type || 'png' == $type) {
                $background_color = imagecolorallocate($thumbImg, 0, 255, 0);  //  指派一个绿色
                imagecolortransparent($thumbImg, $background_color);  //  设置为透明色，若注释掉该行则输出绿色的图
            }
            // 对jpeg图形设置隔行扫描
            if ('jpg' == $type || 'jpeg' == $type) {
                imageinterlace($thumbImg, $interlace);
            }

            // 生成图片
            $imageFun = 'image' . ($type == 'jpg' ? 'jpeg' : $type);
            $imageFun($thumbImg, $savepath);

            imagedestroy($thumbImg);
            imagedestroy($srcImg);
            $save_prefix && $del && File::delete($image);
            return array($savepath, $width, $height);
        }
        return array();
    }

    /**
     * 图片水印
     * @$image  原图
     * @$water 水印图片
     * @$$waterPos 水印位置(0-9) 0为随机，其他代表上中下9个部分位置
     * @param $image
     * @param $water
     * @param int $waterPos
     * @return bool
     */
    function water($image, $water, $waterPos = 9)
    {
        //检查图片是否存在
        if (!file_exists($image) || !file_exists($water)) {
            return false;
        }
        //读取原图像文件
        $imageInfo = $this->getImageInfo($image);
        $image_w = $imageInfo['width']; //取得水印图片的宽
        $image_h = $imageInfo['height']; //取得水印图片的高
        $imageFun = "imagecreatefrom" . $imageInfo['type'];
        $image_im = $imageFun($image);

        //读取水印文件
        $waterInfo = $this->getImageInfo($water);
        $w = $water_w = $waterInfo['width']; //取得水印图片的宽
        $h = $water_h = $waterInfo['height']; //取得水印图片的高
        $waterFun = "imagecreatefrom" . $waterInfo['type'];
        $water_im = $waterFun($water);

        switch ($waterPos) {
            case 0: //随机
                $posX = rand(0, ($image_w - $w));
                $posY = rand(0, ($image_h - $h));
                break;
            case 1: //1为顶端居左
                $posX = 0;
                $posY = 0;
                break;
            case 2: //2为顶端居中
                $posX = ($image_w - $w) / 2;
                $posY = 0;
                break;
            case 3: //3为顶端居右
                $posX = $image_w - $w;
                $posY = 0;
                break;
            case 4: //4为中部居左
                $posX = 0;
                $posY = ($image_h - $h) / 2;
                break;
            case 5: //5为中部居中
                $posX = ($image_w - $w) / 2;
                $posY = ($image_h - $h) / 2;
                break;
            case 6: //6为中部居右
                $posX = $image_w - $w;
                $posY = ($image_h - $h) / 2;
                break;
            case 7: //7为底端居左
                $posX = 0;
                $posY = $image_h - $h;
                break;
            case 8: //8为底端居中
                $posX = ($image_w - $w) / 2;
                $posY = $image_h - $h;
                break;
            case 9: //9为底端居右
                $posX = $image_w - $w;
                $posY = $image_h - $h;
                break;
            default: //随机
                $posX = rand(0, ($image_w - $w));
                $posY = rand(0, ($image_h - $h));
                break;
        }
        //设定图像的混色模式
        imagealphablending($image_im, true);
        //拷贝水印到目标文件
        imagecopy($image_im, $water_im, $posX, $posY, 0, 0, $water_w, $water_h);
        //生成水印后的图片
        $bulitImg = "image" . $imageInfo['type'];
        $bulitImg($image_im, $image);
        //释放内存
        $waterInfo = $imageInfo = null;
        imagedestroy($image_im);
    }

    /**
     * @param $img
     * @return array|bool
     */
    function getImageInfo($img)
    {
        $imageInfo = getimagesize($img);
        if ($imageInfo !== false) {
            $imageType = strtolower(substr(image_type_to_extension($imageInfo[2]), 1));
            $imageSize = filesize($img);
            $info = array(
                "width" => $imageInfo[0],
                "height" => $imageInfo[1],
                "type" => $imageType,
                "size" => $imageSize,
                "mime" => $imageInfo['mime']
            );
            return $info;
        } else {
            return false;
        }
    }
}