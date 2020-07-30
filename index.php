<?php
header("Content-Type: text/html; charset=utf-8");
// require("vendor/autoload.php");
require("src/Base.php");
require("src/Collection.php");

define('DB_HOST', 'localhost');
define('DB_SCHEMA', 'example');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_ENCODING', 'utf8');

$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_SCHEMA;
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
];

if( version_compare(PHP_VERSION, '5.3.6', '<') ){
    if( defined('PDO::MYSQL_ATTR_INIT_COMMAND') ){
        $options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES ' . DB_ENCODING;
    }
}else{
    $dsn .= ';charset=' . DB_ENCODING; //这个就起作用了
}

if( version_compare(PHP_VERSION, '5.3.6', '<') ){
    if( defined('PDO::MYSQL_ATTR_INIT_COMMAND') ){
        $options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES ' . DB_ENCODING; //也是有用的
    }
}else{
    $dsn .= ';charset=' . DB_ENCODING;
}

# the constructor takes the same parameters as the PDO constructor
$Base = new \src\Base($dsn, DB_USER, DB_PASSWORD, $options);

if( version_compare(PHP_VERSION, '5.3.6', '<') && !defined('PDO::MYSQL_ATTR_INIT_COMMAND') ){
    $Base->pdo()->exec("SET NAMES " . DB_ENCODING);
}
// 在创建连接后，加入
//使用PDO查询mysql数据库时，执行prepare,execute后，返回的字段数据全都变为字符型。
$Base->pdo()->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
$Base->pdo()->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

// Work with records:

# read user 1
//var_dump($result = $Base->readItem('tb_article', 8));
# update the username of user 1
//不支持mysql函数
//var_dump($Base->updateItem('tb_article', 8, ['a_title' => "CONCAT(`a_title`, '', '2')"]));
//$pdoStatement = $Base->pdo()->prepare("UPDATE tb_article SET a_title = concat(`a_title`, ',', :name) WHERE id = :id");
//var_dump($pdoStatement);
//$name = 222333;
//var_dump($pdoStatement->execute(array(':name'=> $name,':id'=> 1))); //execute执行一条预处理语句 搭配prepare一起使用，语句没有问题的话，基本上都是返回true，不管是否真正有影响的行数没有，要精准实在地产生变更就需要使用exec或者还是我们使用execute的话，接着后面使用rowCount获得受影响的行数
////exec 执行一条 SQL 语句，并返回受影响的行数
//var_dump($pdoStatement->rowCount());
# create a user
// $Base->createItem('tb_article', ['a_title' => 'jane.doe', 'a_cont' => 'jane@example.com']);
# delete user 1
// $Base->deleteItem('tb_article', 23);


//Work with collections:

# read all users
//var_export($Base->find('tb_article')->read());
# read the users that are marked as verified in a desc order
//var_export($Base->find('user')->whereEqual('is_verified', 1)->orderDesc('id')->read());
# read the user with the most reputation
//var_export($result = $Base->find('user')->limit(2)->orderDesc('id')->readRecord());
# mark users 1 and 3 as verified
//var_export($Base->find('user')->whereIn('id', [1,2,3])->update(['is_verified' => 1]));
# count the users that don't have a location
//var_export($Base->find('user')->whereNull('location')->count());
# plain sql conditions are also supported
//var_dump($Base->find('user')->where('is_verified = ?', [1])->read());
//var_export($Base->find('user')->where('is_verified = ?', [1])->read());

//Handle relationships:

# read the users that have a featured post
//var_dump($Base->find('user')->has('post')->whereEqual('post.is_featured', 1)->read());
//下面是错误的
//var_dump($Base->find('user')->has('post')->has('post_tag')->whereEqual('post.is_featured', 1)->read());
//原义是user left join post, post在left join post_tag,但是最后has里面写的left join表名都是同主表user进行left join,是不对的,不符合我的实际情况,生成如下sql语句
//"SELECT `user`.* FROM `user` LEFT JOIN `post` ON `user`.`id` = `post`.`user_id` LEFT JOIN `post_tag` ON `user`.`id` = `post_tag`.`user_id` WHERE 1 AND `post`.`is_featured` = ?"
//需要很复杂的链表操作,这个has方法不适用,只能写原生的sql来处理
# read the posts of user 1
//var_dump($Base->find('post')->belongsTo('user')->whereEqual('user.id', 1)->read());
# read the posts that are tagged "php"
//var_dump($Base->find('post')->hasAndBelongsTo('tag')->whereIn('tag.name', ['literature', 'history'])->read('distinct post.*'));
//语句使用了join left,整个所有表都在结果里面的话，不会有重复行数据出现，但是单看某一个表的数据（整个结果是多个表在一起的结果）可能就有重复行数据，
//刚好要返回的内容不是整个结果，是某个表的结果就需要我们使用distinct mysql关键字，distinct必须放在要查询字段的开头 这样是不行的
//select id,distinct name from user； 或者这样也是不行的同理 select distinct id,distinct name from user；只能是select distinct id, name from user，意思是整行（由id,name组成的行）不能有相同的出现，但是id或者name可以有重复的出现，但是不能一起的数据有重复出现
//如果要查询不重复的记录，有时候可以用group by
//结果得到只是post.*表的内容，但是可能有重复行数据出现，我使用了distinct post.*去除重复
//hasAndBelongsTo方法的参数,是帮我们生成了中间表在内的3表left join
//并且中间表的名称需要是post_tag,但是hasAndBelongsTo方法的参数只需要写tag即可
//生成的sql语句如下
//"SELECT `post`.* FROM `post`
//			LEFT JOIN `post_tag` ON `post`.`id` = `post_tag`.`post_id`
//			LEFT JOIN `tag` ON `tag`.`id` = `post_tag`.`tag_id` WHERE 1 AND `tag`.`name` = ?"
# unconventional FK names are also supported
//var_dump($Base->find('user')->has('post', 'author_id')->whereEqual('user.id', 1)->read()); //默认生成的sql语句中left join部分是user left join post on post.user_id = user.id
//如果post表中与user表关联的字段不是user_id,是其他字段的话,就可以使用has的第二个参数

//Execute queries:

# read all users
//以下是Base类自有的方法，当使用了find之后，就是使用的Collection类中提供的方法
//var_dump($Base->read('SELECT * FROM user'));
# read user 1
//var_dump($Base->readRecord('SELECT * FROM user WHERE id = ?', [1]));
# read the username of user 1
//var_dump($Base->readField('SELECT username FROM user WHERE id = ?', [1]));
# read all usernames
//var_dump($Base->readFields('SELECT username FROM user'));
# update all users
//var_dump($Base->update('UPDATE user SET is_verified = ?', [1]));