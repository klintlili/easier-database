# easier-database
Base is a simple library that makes it easier to work with mysql databases in PHP.

### Features

- Simple [简单]
- Intuitive [直观]
- Independent [独立]
- Secure [安全]
- Based on [PDO](http://php.net/manual/en/book.pdo.php)

### Installation

Include both `Base.php` and `Collection.php` or install [the composer package](https://packagist.org/packages/klintlili/easier-database).
See in detail `index.php` Examples in the document.

### Examples

Connect to a database:

```php
# the constructor takes the same parameters as the PDO constructor
$Base = new \Base\Base('mysql:host=localhost;dbname=example', 'username', 'password');
```

Work with records:

```php
# read user 1
$Base->readItem('user', 1);
# update the username of user 1
$Base->updateItem('user', 1, ['username' => 'john.doe']);
# create a user
$Base->createItem('user', ['username' => 'jane.doe', 'email' => 'jane@example.com']);
# delete user 1
$Base->deleteItem('user', 1);
```

Work with collections:

```php
# read all users
$Base->find('user')->read();
# read the users that are marked as verified in a desc order
$Base->find('user')->whereEqual('is_verified', 1)->orderDesc('id')->read();
# read the user with the most reputation
$Base->find('user')->limit(1)->orderDesc('reputation')->readRecord();
# mark users 1 and 3 as verified
$Base->find('user')->whereIn('id', [1, 3])->update(['is_verified' => 1]);
# count the users that don't have a location
$Base->find('user')->whereNull('location')->count();
# plain sql conditions are also supported
$Base->find('user')->where('is_verified = ?', [1])->read();
```

Handle relationships:

```php
# read the users that have a featured post
$Base->find('user')->has('post')->whereEqual('post.is_featured', 1)->read();
# read the posts of user 1
$Base->find('post')->belongsTo('user')->whereEqual('user.id', 1)->read();
# read the posts that are tagged "php"
$Base->find('post')->hasAndBelongsTo('tag')->whereEqual('tag.name', 'php')->read();
# unconventional FK names are also supported
$Base->find('user')->has('post', 'author_id')->whereEqual('user.id', 1)->read();
```

Execute queries:

```php
# read all users
$Base->read('SELECT * FROM user');
# read user 1
$Base->readRecord('SELECT * FROM user WHERE id = ?', [1]);
# read the username of user 1
$Base->readField('SELECT username FROM user WHERE id = ?', [1]);
# read all usernames
$Base->readFields('SELECT username FROM user');
# update all users
$Base->update('UPDATE INTO user SET is_verified = ?', [1]);
```