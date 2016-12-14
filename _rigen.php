<?php /*-----------------------------------------------------------------------
 PHP RIGEN - PHP Responsive Image Generator
 Copyright Jos Faber

 This software is a simplified version of PHP Adaptive Images (PHP-AI)
 https://github.com/MattWilcox/Adaptive-Images and was created to work
 with both Apache and NGINX.

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

     http://www.apache.org/licenses/LICENSE-2.0

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.

 @todo add explaination and usage
-----------------------------------------------------------------------------*/

class PHP_RIGEN
{
  /*
   * array of sizes
   */
  private $sizes = array(480, 640, 1024, 1280, 1920);
  /*
   * cache dir name, relative to this dir
   */
  private $cache_dir_name = 'img-cache';
  /*
   * output image quality
   */
  private $jpg_quality = 95;


  /* --------------------------------------------------------
   * From here on changes to core code are on you ;-)
   */
  private $cache_dir;
  private $filename;
  private $is_mobile;
  private $has_resolution;
  private $resolution;
  private $i_width;
  private $i_height;

  public function __construct($filename) {
    if (!file_exists($filename)) {
      exit;
    }
    $this->cache_dir = __DIR__.DIRECTORY_SEPARATOR.$this->cache_dir_name.DIRECTORY_SEPARATOR;
    $this->filename = $filename;
    $this->basename = basename($this->filename);
    $this->dirname = pathinfo($this->filename, PATHINFO_DIRNAME);
    $this->is_mobile = strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'mobile'); // plan B, if no cookie
    $this->has_resolution = isset($_COOKIE["resolution"]); // check if cookie
    if ($this->has_resolution) {
      $this->resolution = (int)$_COOKIE["resolution"];
    }
    $size = getimagesize($this->filename); // origin size
    $this->i_width = $size[0];
    $this->i_height = $size[1];
  }

  public function render() {
    if ($this->has_resolution) { // if user has resolution in cookie
      if ($this->i_width>$this->resolution && $this->i_height>$this->resolution) { // if image bigger in both width and height
        $size = $this->nextSmallerSize();
        $this->serveFile( $size.DIRECTORY_SEPARATOR.$this->filename, TRUE, $size ); // cached scaled down
      } else {
        $this->serveFile( $this->filename ); // original
      }
    } else { // if user has no resolution (plan B)
      if ($this->is_mobile) { // if is mobile
        $size = $this->sizes[0];
        $this->serveFile( $size.DIRECTORY_SEPARATOR.$this->filename, TRUE, $size ); // cached smallest
      } else {
        // $this->serveFile( $this->filename ); // original
        $size = end($this->sizes); // since this is plan B, let's not serve original, but biggest
        $this->serveFile( $size.DIRECTORY_SEPARATOR.$this->filename, TRUE, $size ); // cached biggest
      }
    }
  }

  private function nextSmallerSize($i=null) {
    if (is_null($i)) { $i = count($this->sizes)-1; }
    return $this->resolution>$this->sizes[$i]?
                $this->sizes[$i]:
                $this->nextSmallerSize($i-1);
  }

  private function serveFile($filename, $cached=FALSE, $size=1920) {
    $mime = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if ($cached) {
      $dir = $this->cache_dir.pathinfo($filename, PATHINFO_DIRNAME);
      $filename = $this->cache_dir.$filename;
      if (!file_exists($dir) || !is_dir($dir) || !file_exists($filename) || filemtime($filename)<filemtime($this->filename)) { // if exist and timestamp newer serve cache
        if (!file_exists($dir)) { // there is no dir yet
          mkdir($dir, 0777, TRUE); // make dir for cached file
          /*
           * @todo check if mkdir worked / rights to create
           */
        }

        $ratio = $this->i_height/$this->i_width;
        $dst_height = ceil($size * $ratio);
        $dst = ImageCreateTrueColor($size, $dst_height);

        switch ($mime) {
          case 'png': $src = @ImageCreateFromPng($this->filename); break;
          case 'gif': $src = @ImageCreateFromGif($this->filename); break;
          default: $src = @ImageCreateFromJpeg($this->filename); ImageInterlace($dst, true); break; // Enable interlancing (progressive JPG, smaller size file)
        }

        if($mime=='png'){
          imagealphablending($dst, false);
          imagesavealpha($dst,true);
          imagefilledrectangle($dst, 0, 0, $size, $dst_height, imagecolorallocatealpha($dst, 255, 255, 255, 127));
        }

        ImageCopyResampled($dst, $src, 0, 0, 0, 0, $size, $dst_height, $this->i_width, $this->i_height);
        ImageDestroy($src);

        switch ($mime) {
          case 'png': ImagePng($dst, $filename); break;
          case 'gif': ImageGif($dst, $filename); break;
          default: ImageJpeg($dst, $filename, $this->jpg_quality); break;
        }
        ImageDestroy($dst);
        /*
         * @todo check if creation worked / file rights
         */
      }
    }
    header("Content-Type: image/".($mime=="jpg"?"jpeg":$mime));
    header("Cache-Control: private, max-age=604800");
    header('Expires: '.gmdate('D, d M Y H:i:s', time()+604800).' GMT');
    header('Content-Length: '.filesize($filename));
    readfile($filename);
    exit();
  }

}

$rigen = new PHP_RIGEN( parse_url(urldecode($_SERVER['QUERY_STRING']),PHP_URL_PATH) );
$rigen->render();
