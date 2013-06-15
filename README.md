fileRepeatCheck
===============
# my email: bjzhush#gmail.com
a simple repeat file check and uniq script


1.检查重复文件，显示结果或只输出供去重的结果
UniqFile.php 共3个参数，
    -dir为必须，需要指定要检查的路径
    -hash 默认为true，即通过文件头100000个字符的md5 hash进行文件唯一性的对比，若指定为false，则通过文件size检查
    -showdel 默认为false，若指定为true，则将所有重复的文件去除每组第一个，然后全部打印出来（已做了对空格的转义）
 
  建议使用sudo权限执行，避免无权限访问的情况（虽然对于权限拒绝也做了判断和记录）

usage: /usr/bin/php UniqFile.php -dir=/your/path/ [-hash=false] [-showdel=true]

2.去重程序
 可用
 /usr/bin/php UniqFile.php -dir=/your/path/  -showdel=true >todel.txt
 然后bash delFilesReadFromFile.sh
 输入todel.txt 交给rm进行删除
 注意在你使用这个脚本之前，确定你非常清楚并且你肯定你要删除的这些文件没有问题 ！！！！！！！！！！
