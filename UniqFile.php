<?php
/**
 * UniqFile 
 * Check repeat files under a specified directory
 * usage: php UniqFile.php -dir=/your/path [-hash=false] [-showdel=true] [-ext=null/txt/php]
 * ext = null stands for match files with no ext(file ext is seprated by .)
 * use file section to hash file to improve efficiency 
 * @ToDo: add file delete ?
 * @version v0.1
 * @author Shuai Zhu <bjzhush@gmail.com> 
 */
class UniqFile
{
    protected $fileList        = array();
    protected $dirToCheck      = FALSE;
    protected $emptyFileCount  = 0;
    protected $allFileCount    = 0;
    protected $extFileCount    = 0;
    protected $repeatFileCount = 0;
    protected $deniedFileCount = 0;
    protected $sizeList        = array();
    protected $hashList        = array();
    protected $timeCost        = 0;
    protected $extList         = array();

    protected $supportParameters = array(
            '-dir'     => TRUE,
            '-hash'    => TRUE,
            '-showdel' =>FALSE,
            '-ext'     =>FALSE,
            );

    public function __construct($argv) {
        //record start time
        $this->starttime = microtime();
        $tmp = explode(' ',$this->starttime);
        $this->starttime = (float)($tmp['0']+$tmp['1']);

        //check if it's in web server now
        if (empty($argv)) {
            exit('This file should be execued under cli mode');
        }

        //check if  No parameter was  passed
        if (count($argv) == 1) {
            exit('usage: php UniqFile.php -dir=/your/path [-hash=false][-showdel=true]');
        }

        //init parameter
        //unset the default param ,current file name
        unset($argv['0']);
        foreach ($argv as $k => $v) {
            $arrtmp = explode( '=' , $v);
            //illage parameter passed with more than one = 
            if (count($arrtmp) > 2) {
                exit('Illage parameter '.$v.', For only one = allowed in one parameter');
            }

            if (array_key_exists($arrtmp['0'], $this->supportParameters)) {

                if ($arrtmp['0'] == '-dir') {
                    $this->supportParameters['-dir'] = $arrtmp['1'];
                } elseif ($arrtmp['0'] == '-hash') {
                    if (!in_array(strtoupper($arrtmp['1']), array('TRUE','FALSE'))) {
                        exit('hash  value should be true/false');
                    }
                    $this->supportParameters['-hash'] = (strtoupper($arrtmp['1']) === 'TRUE');
                } elseif ($arrtmp['0'] == '-showdel') {
                    if (!in_array(strtoupper($arrtmp['1']), array('TRUE','FALSE'))) {
                        exit('showdel  value should be true/false');
                    }
                    $this->supportParameters['-showdel'] = (strtoupper($arrtmp['1']) === 'TRUE');
                } elseif ($arrtmp['0']== '-ext') {
                    if (strpos($arrtmp['1'], '/') == FALSE) {
                        $this->extList[] = str_replace('/[^a-zA-Z\s]/', '', $arrtmp['1']);
                    } else {
                        $tmparr = explode('/', $arrtmp['1']);
                        foreach ($tmparr as $kb => $vb) {
                            $this->extList[] = str_replace('/[^a-zA-Z\s]/', '',$vb);
                        }
                    }
                } else {
                    //ignore other parms passed
                    exit('Unidentified parms '.$v.' Passed !');
                
                }
            }
        }
    }
    
    //input a path ,set all file into $this->fileList
    public function scanDirectory($path) {
        $path = rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        $tmpFileList = @scandir($path);
        if (is_array($tmpFileList)) {
            $newTmpFileList = array_flip($tmpFileList);
            unset($newTmpFileList['.']);
            unset($newTmpFileList['..']);
            foreach ($newTmpFileList as $kb => $vb) {
                if (!is_dir($path.$kb)) {
                    $this->allFileCount++;
                    //match file ext
                    if (count($this->extList)) {
                        $tmpExplode = explode('.', $kb);
                        //has no . in file name
                        if (count($tmpExplode) == 1 ) {
                            $currentFileExt = 'null';
                        } else {
                            $currentFileExt = $tmpExplode[count($tmpExplode) - 1];
                        }

                        if (in_array($currentFileExt, $this->extList, TRUE )) {
                            array_push($this->fileList, $path.$kb);
                        }

                    } else {
                        array_push($this->fileList, $path.$kb);
                    }
                }
            }
            foreach ($tmpFileList as $k => $v) {
                if ($v != '.' && $v != '..' && is_dir($path.$v)) {
                    $this->scanDirectory($path.$v);
                }
            
            }
        
        }
    
    }

    
    public function check(){
        //format directory string
        $this->dirToCheck = rtrim($this->supportParameters['-dir'], DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        if (!is_dir($this->dirToCheck)) {
            exit($this->dirToCheck.'is not a effective directory');
        }

        $this->scanDirectory($this->dirToCheck);
        $this->fileList = array_values($this->fileList);
        foreach ($this->fileList as $k => $v) {
            if (@filesize($v) === FALSE) {
                unset($this->fileList[$k]);
                $this->deniedFileCount++;
            } else {
                $this->fileList[$k] = array(
                        'filename' => $v,
                        'filesize' => filesize($v),
                        'hashvalue' => NULL,
                        );
            }
        }
        $this->extFileCount = count($this->fileList);
        foreach ($this->fileList as $k => $v) {
            if ($v['filesize'] == 0) {
                unset($this->fileList[$k]);
                $this->emptyFileCount++;
            } else {
                if (empty($this->sizeList[$v['filesize']])) {
                    $this->sizeList[$v['filesize']] = array();
                }
                array_push($this->sizeList[$v['filesize']], $v);
            }
        }

        foreach ($this->sizeList as $k => $v) {
            if (count($v) == 1) {
                unset($this->sizeList[$k]);
            } else {
                $this->repeatFileCount++;
            }
        }
        //check hash
        if ($this->supportParameters['-hash']) {
            $this->repeatFileCount = 0;
            foreach ($this->sizeList as $k => $v) {
                foreach ($v as $kb => $vb) {
                    $handle = @fopen($vb['filename'], 'r');
                    if ($handle === FALSE) {
                        $this->deniedFileCount++;
                    } else {
                        $size = $vb['filesize'] > 100000 ? 100000 : $vb['filesize'];
                        $tmpPartilyFile = fread($handle, $size);
                        $this->sizeList[$k][$kb]['hashvalue'] = md5($tmpPartilyFile);
                        fclose($handle);
                    
                    }
                }
            }
            foreach ($this->sizeList as $k => $v) {
                foreach ($v as $kb => $vb) {
                    if (empty($this->hashList[$vb['hashvalue']])) {
                        $this->hashList[$vb['hashvalue']] = array();
                    }
                    array_push($this->hashList[$vb['hashvalue']], $vb);
                }
            }
            foreach ($this->hashList as $k => $v) {
                if (count($v) == 1) {
                    unset($this->hashList[$k]);
                }
            }
        $this->repeatFileCount = count($this->hashList);
        $this->sizeList = $this->hashList;
        }


        //record end time
        $this->endtime = microtime();
        $tmp = explode(' ',$this->endtime);
        $this->endtime = (float)($tmp['0']+$tmp['1']);

        $this->timeCost = (float)($this->endtime-$this->starttime);

        //show result 


        //show all repeat files exclude first one in the group 
        if ($this->supportParameters['-showdel']) {
            foreach ($this->sizeList as $k => $v) {
                foreach ($v as $kb => $vb) {
                    if ($kb != 0) {
                        echo str_replace(' ','\\ ',$vb['filename']);
                        echo "\n";
                    }
                }
            }
            exit;
        
        }


        foreach ($this->sizeList as $k => $v) {
            echo "^^^^^^^^^^^^^^^^^^^^^\n";

            foreach ($v as $kb => $vb) {
                echo  $vb['filename'] ;
                echo "\n";
            }
            echo "*********************\n";
        }
        echo "目前使用";
        echo $this->supportParameters['-hash'] ? '文件头hash':'文件大小' ;
        echo "作为判断文件是否相同的标准\n";
        echo "当前检查目录为 ".$this->supportParameters['-dir'].",\n" ;
        echo "共使用了 $this->timeCost 秒\n";
        if (count($this->extList)) {
            echo "共有".$this->extFileCount.'个符合'.implode(',', $this->extList)."的文件\n";
        
        }
        echo "其中共有 $this->deniedFileCount 个文件没有权限读取,空文件共有 $this->emptyFileCount 个\n";
        echo "总共检查了$this->allFileCount 个文件， 重复的有 $this->repeatFileCount 组\n";
    
        
    
    }
}


$instance = new UniqFile($argv);
$instance->check();

