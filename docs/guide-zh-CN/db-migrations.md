数据库迁移
===========

在开发和维护一个数据库驱动的应用程序时，
数据库的结构会随代码的改变而改变。
例如，在开发应用程序的过程中，会增加一张新表且必须得加进来；
在应用程序被部署到生产环境后，需要建立一个索引来提高查询的性能等等。
因为一个数据库结构发生改变的时候源代码也经常会需要做出改变，
Yii 提供了一个 *数据库迁移* 功能，该功能可以记录数据库的变化，
以便使数据库和源代码一起受版本控制。

如下的步骤向我们展示了数据库迁移工具是如何为开发团队所使用的：

1. Tim 创建了一个新的迁移对象（例如，创建一张新的表单，改变字段的定义等）。
2. Tim 将这个新的迁移对象提交到代码管理系统（例如，Git，Mercurial）。
3. Doug 从代码管理系统当中更新版本并获取到这个新的迁移对象。
4. Doug 把这个迁移对象提交到本地的开发数据库当中，
   这样一来，Doug 同步了 Tim 所做的修改。

如下的步骤向我们展示了如何发布一个附带数据库迁移的新版本到生产环境当中：

1. Scott 为一个包含数据库迁移的项目版本创建了一个发布标签。
2. Scott 把发布标签的源代码更新到生产环境的服务器上。
3. Scott 把所有的增量数据库迁移提交到生产环境数据库当中。

Yii 提供了一整套的迁移命令行工具，通过这些工具你可以：

* 创建新的迁移；
* 提交迁移；
* 恢复迁移；
* 重新提交迁移；
* 现实迁移历史和状态。

所有的这些工具都可以通过 `yii migrate` 命令来进行操作。
在这一章节，我们将详细的介绍如何使用这些工具来完成各种各样的任务。
你也可以通过 `yii help migrate` 命令来获取每一种工具的具体使用方法。

> Note: 迁移不仅仅只作用于数据库表，
  它同样会调整现有的数据来适应新的表单、创建 RBAC 分层、又或者是清除缓存。


## 创建迁移 <span id="creating-migrations"></span>

使用如下命令来创建一个新的迁移：

```
yii migrate/create <name>
```

必填参数 `name` 的作用是对新的迁移做一个简要的描述。
例如，如果这个迁移是用来创建一个叫做 *news* 的表单的，
那么你可以使用 `create_news_table` 这个名称并运行如下命令：

```
yii migrate/create create_news_table
```

> Note: 因为 `name` 参数会被用来生成迁移的类名的一部分，
  所以该参数应当只包含字母、数字和下划线。

如上命令将会在 `@app/migrations` 目录下创建一个新的名为 `m150101_185401_create_news_table.php` 的 PHP 类文件。
该文件包含如下的代码，它们用来声明一个迁移类 `m150101_185401_create_news_table`，
并附有代码框架：

```php
<?php

use Yiisoft\Db\Migration;

class m150101_185401_create_news_table extends Migration
{
    public function up()
    {

    }

    public function down()
    {
        echo "m101129_185401_create_news_table cannot be reverted.\n";

        return false;
    }

    /*
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
    }

    public function safeDown()
    {
    }
    */
}
```

每个数据库迁移都会被定义为一个继承自 [[Yiisoft\Db\Migration]] 的 PHP 类。
类的名称按照 `m<YYMMDD_HHMMSS>_<Name>` 的格式自动生成，其中

* `<YYMMDD_HHMMSS>` 指执行创建迁移命令的 UTC 时间。
* `<Name>` 和你执行命令时所带的 `name` 参数值相同。

在迁移类当中，你应当在 `up()` 方法中编写改变数据库结构的代码。
你可能还需要在 `down()` 方法中编写代码来恢复由 `up()` 方法所做的改变。
当你通过 migration 升级数据库时， `up()` 方法将会被调用，反之， `down()` 将会被调用。
如下代码展示了如何通过迁移类来创建一张 `news` 表：

```php

use Yiisoft\Db\Schema;
use Yiisoft\Db\Migration;

class m150101_185401_create_news_table extends \Yiisoft\Db\Migration
{
    public function up()
    {
        $this->createTable('news', [
            'id' => Schema::TYPE_PK,
            'title' => Schema::TYPE_STRING . ' NOT NULL',
            'content' => Schema::TYPE_TEXT,
        ]);
    }

    public function down()
    {
        $this->dropTable('news');
    }

}
```

> Note: 并不是所有迁移都是可恢复的。例如，如果 `up()` 方法删除了表中的一行数据，
  这将无法通过 `down()`  方法来恢复这条数据。有时候，你也许只是懒得去执行 `down()` 方法了，
  因为它在恢复数据库迁移方面并不是那么的通用。在这种情况下，
  你应当在 `down()` 方法中返回 `false` 来表明这个 migration 是无法恢复的。

migration 的基类 [[Yiisoft\Db\Migration]] 通过 [[Yiisoft\Db\Migration::db|db]] 属性来连接了数据库。
你可以通过 [配合数据库工作](db-dao.md#working-with-database-schema-) 
章节中所描述的那些方法来操作数据库表。

当你通过 migration 创建一张表或者字段的时候，你应该使用 *抽象类型* 而不是 *实体类型*，
这样一来你的迁移对象就可以从特定的 DBMS 当中抽离出来。
[[Yiisoft\Db\Schema]] 类定义了一整套可用的抽象类型常量。这些常量的格式为 `TYPE_<Name>`。
例如，`TYPE_PK` 指代自增主键类型；`TYPE_STRING` 指代字符串类型。
当迁移对象被提交到某个特定的数据库的时候，这些抽象类型将会被转换成相对应的实体类型。
以 MySQL 为例，`TYPE_PK` 将会变成 `int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY`，
而 `TYPE_STRING` 则变成 `varchar(255)`。

在使用抽象类型的时候，你可以添加额外的约束条件。在上面的例子当中，
`NOT NULL` 被添加到 `Schema::TYPE_STRING` 当中来指定该字段不能为空。

> Tip: 抽象类型和实体类型之间的映射关系是由每个具体的 `QueryBuilder` 
  类当中的 [[Yiisoft\Db\QueryBuilder::$typeMap|$typeMap]] 属性所指定的。
  
Since version 2.0.6, you can make use of the newly introduced schema builder which provides more convenient way of defining column schema.
So the migration above could be written like the following:

```php
<?php

use Yiisoft\Db\Migration;

class m150101_185401_create_news_table extends Migration
{
    public function up()
    {
        $this->createTable('news', [
            'id' => $this->primaryKey(),
            'title' => $this->string()->notNull(),
            'content' => $this->text(),
        ]);
    }

    public function down()
    {
        $this->dropTable('news');
    }
}
```

A list of all available methods for defining the column types is available in the API documentation of [[Yiisoft\Db\SchemaBuilderTrait]].


## Generating Migrations <span id="generating-migrations"></span>

Since version 2.0.7 migration console provides a convenient way to create migrations.

If the migration name is of a special form, for example `create_xxx` or `drop_xxx` then the generated migration
file will contain extra code, in this case for creating/dropping tables.
In the following all variants of this feature are described.

### Create Table

```php
yii migrate/create create_post
```

generates

```php
/**
 * Handles the creation for table `post`.
 */
class m150811_220037_create_post extends Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->createTable('post', [
            'id' => $this->primaryKey()
        ]);
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropTable('post');
    }
}
```

To create table fields right away, specify them via `--fields` option.

```php
yii migrate/create create_post --fields="title:string,body:text"
```

generates

```php
/**
 * Handles the creation for table `post`.
 */
class m150811_220037_create_post extends Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->createTable('post', [
            'id' => $this->primaryKey(),
            'title' => $this->string(),
            'body' => $this->text(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropTable('post');
    }
}

```

You can specify more field parameters.

```php
yii migrate/create create_post --fields="title:string(12):notNull:unique,body:text"
```

generates

```php
/**
 * Handles the creation for table `post`.
 */
class m150811_220037_create_post extends Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->createTable('post', [
            'id' => $this->primaryKey(),
            'title' => $this->string(12)->notNull()->unique(),
            'body' => $this->text()
        ]);
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropTable('post');
    }
}
```

> Note: primary key is added automatically and is named `id` by default. If you want to use another name you may
> specify it explicitly like `--fields="name:primaryKey"`.

#### Foreign keys

Since 2.0.8 the generator supports foreign keys using the `foreignKey` keyword.

```php
yii migrate/create create_post --fields="author_id:integer:notNull:foreignKey(user),category_id:integer:defaultValue(1):foreignKey,title:string,body:text"
```

generates

```php
/**
 * Handles the creation for table `post`.
 * Has foreign keys to the tables:
 *
 * - `user`
 * - `category`
 */
class m160328_040430_create_post extends Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->createTable('post', [
            'id' => $this->primaryKey(),
            'author_id' => $this->integer()->notNull(),
            'category_id' => $this->integer()->defaultValue(1),
            'title' => $this->string(),
            'body' => $this->text(),
        ]);

        // creates index for column `author_id`
        $this->createIndex(
            'idx-post-author_id',
            'post',
            'author_id'
        );

        // add foreign key for table `user`
        $this->addForeignKey(
            'fk-post-author_id',
            'post',
            'author_id',
            'user',
            'id',
            'CASCADE'
        );

        // creates index for column `category_id`
        $this->createIndex(
            'idx-post-category_id',
            'post',
            'category_id'
        );

        // add foreign key for table `category`
        $this->addForeignKey(
            'fk-post-category_id',
            'post',
            'category_id',
            'category',
            'id',
            'CASCADE'
        );
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        // drops foreign key for table `user`
        $this->dropForeignKey(
            'fk-post-author_id',
            'post'
        );

        // drops index for column `author_id`
        $this->dropIndex(
            'idx-post-author_id',
            'post'
        );

        // drops foreign key for table `category`
        $this->dropForeignKey(
            'fk-post-category_id',
            'post'
        );

        // drops index for column `category_id`
        $this->dropIndex(
            'idx-post-category_id',
            'post'
        );

        $this->dropTable('post');
    }
}
```

The position of the `foreignKey` keyword in the column description doesn't
change the generated code. That means:

- `author_id:integer:notNull:foreignKey(user)`
- `author_id:integer:foreignKey(user):notNull`
- `author_id:foreignKey(user):integer:notNull`

All generate the same code.

The `foreignKey` keyword can take a parameter between parenthesis which will be
the name of the related table for the generated foreign key. If no parameter
is passed then the table name will be deduced from the column name.

In the example above `author_id:integer:notNull:foreignKey(user)` will generate a
column named `author_id` with a foreign key to the `user` table while
`category_id:integer:defaultValue(1):foreignKey` will generate a column
`category_id` with a foreign key to the `category` table.

### Drop Table

```php
yii migrate/create drop_post --fields="title:string(12):notNull:unique,body:text"
```

generates

```php
class m150811_220037_drop_post extends Migration
{
    public function up()
    {
        $this->dropTable('post');
    }

    public function down()
    {
        $this->createTable('post', [
            'id' => $this->primaryKey(),
            'title' => $this->string(12)->notNull()->unique(),
            'body' => $this->text()
        ]);
    }
}
```

### Add Column

If the migration name is of the form `add_xxx_to_yyy` then the file content would contain `addColumn` and `dropColumn`
statements necessary.

To add column:

```php
yii migrate/create add_position_to_post --fields="position:integer"
```

generates

```php
class m150811_220037_add_position_to_post extends Migration
{
    public function up()
    {
        $this->addColumn('post', 'position', $this->integer());
    }

    public function down()
    {
        $this->dropColumn('post', 'position');
    }
}
```

### Drop Column

If the migration name is of the form `drop_xxx_from_yyy` then the file content would contain `addColumn` and `dropColumn`
statements necessary.

```php
yii migrate/create drop_position_from_post --fields="position:integer"
```

generates

```php
class m150811_220037_drop_position_from_post extends Migration
{
    public function up()
    {
        $this->dropColumn('post', 'position');
    }

    public function down()
    {
        $this->addColumn('post', 'position', $this->integer());
    }
}
```

### Add Junction Table

If the migration name is in if the form of `create_junction_xxx_and_yyy` then code necessary to create junction table
will be generated.

```php
yii migrate/create create_junction_post_and_tag --fields="created_at:dateTime"
```

generates

```php
/**
 * Handles the creation for table `post_tag`.
 * Has foreign keys to the tables:
 *
 * - `post`
 * - `tag`
 */
class m160328_041642_create_junction_post_and_tag extends Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->createTable('post_tag', [
            'post_id' => $this->integer(),
            'tag_id' => $this->integer(),
            'created_at' => $this->dateTime(),
            'PRIMARY KEY(post_id, tag_id)',
        ]);

        // creates index for column `post_id`
        $this->createIndex(
            'idx-post_tag-post_id',
            'post_tag',
            'post_id'
        );

        // add foreign key for table `post`
        $this->addForeignKey(
            'fk-post_tag-post_id',
            'post_tag',
            'post_id',
            'post',
            'id',
            'CASCADE'
        );

        // creates index for column `tag_id`
        $this->createIndex(
            'idx-post_tag-tag_id',
            'post_tag',
            'tag_id'
        );

        // add foreign key for table `tag`
        $this->addForeignKey(
            'fk-post_tag-tag_id',
            'post_tag',
            'tag_id',
            'tag',
            'id',
            'CASCADE'
        );
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        // drops foreign key for table `post`
        $this->dropForeignKey(
            'fk-post_tag-post_id',
            'post_tag'
        );

        // drops index for column `post_id`
        $this->dropIndex(
            'idx-post_tag-post_id',
            'post_tag'
        );

        // drops foreign key for table `tag`
        $this->dropForeignKey(
            'fk-post_tag-tag_id',
            'post_tag'
        );

        // drops index for column `tag_id`
        $this->dropIndex(
            'idx-post_tag-tag_id',
            'post_tag'
        );

        $this->dropTable('post_tag');
    }
}
```

### 事务迁移 <span id="transactional-migrations"></span>

当需要实现复杂的数据库迁移的时候，确定每一个迁移的执行是否成功或失败就变得相当重要了，
因为这将影响到数据库的完整性和一致性。为了达到这个目标，我们建议你把每个迁移里面的
数据库操作都封装到一个 [transaction](db-dao.md#performing-transactions-) 里面。
 
实现事务迁移的一个更为简便的方法是把迁移的代码都放到 `safeUp()` 和 `safeDown()` 方法里面。
它们与 `up()` 和 `down()` 的不同点就在于它们是被隐式的封装到事务当中的。
如此一来，只要这些方法里面的任何一个操作失败了，那么所有之前的操作都会被自动的回滚。

在如下的例子当中，除了创建 `news` 表以外，我们还插入了一行初始化数据到表里面。

```php

use Yiisoft\Db\Schema;
use Yiisoft\Db\Migration;

class m150101_185401_create_news_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('news', [
            'id' => $this->primaryKey(),,
            'title' => $this->string()->notNull(),
            'content' => $this->text(),
        ]);
        
        $this->insert('news', [
            'title' => 'test 1',
            'content' => 'content 1',
        ]);
    }

    public function safeDown()
    {
        $this->delete('news', ['id' => 1]);
        $this->dropTable('news');
    }
}
```

需要注意的是，当你在 `safeUp()` 当中执行多个数据库操作的时候，你应该在 `safeDown()` 方法当中反转它们的执行顺序。
在上面的例子当中，我们在 `safeUp()` 方法当中首先创建了一张表，然后插入了一条数据；而在 `safeDown()` 方法当中，
我们首先删除那一行数据，然后才删除那张表。

> Note: 并不是所有的数据库都支持事务。有些数据库查询也是不能被放倒事务里面的。
  在 [implicit commit](http://dev.mysql.com/doc/refman/5.1/en/implicit-commit.html) 章节当中有相关的例子可以参考。
  如果遇到这种情况的话，那么你应该使用 `up()` 和 `down()` 方法进行替代。


### 访问数据库的方法 <span id="db-accessing-methods"></span>

迁移的基类 [[Yiisoft\Db\Migration]] 提供了一整套访问和操作数据库的方法。
你可能会发现这些方法的命名和 [[Yiisoft\Db\Command]] 类提供的 [DAO 方法](db-dao.md) 很类似。
例如，[[Yiisoft\Db\Migration::createTable()]] 方法可以创建一张新的表，
这和 [[Yiisoft\Db\Command::createTable()]] 的功能是一模一样的。

使用 [[Yiisoft\Db\Migration]] 所提供的方法的好处在于你不需要再显式的创建 [[Yiisoft\Db\Command]] 实例，
而且在执行每个方法的时候都会显示一些有用的信息来告诉我们数据库操作是不是都已经完成，
还有它们完成这些操作花了多长时间等等。

如下是所有这些数据库访问方法的列表：

* [[Yiisoft\Db\Migration::execute()|execute()]]: 执行一条 SQL 语句
* [[Yiisoft\Db\Migration::insert()|insert()]]: 插入单行数据
* [[Yiisoft\Db\Migration::batchInsert()|batchInsert()]]: 插入多行数据
* [[Yiisoft\Db\Migration::update()|update()]]: 更新数据
* [[Yiisoft\Db\Migration::delete()|delete()]]: 删除数据
* [[Yiisoft\Db\Migration::createTable()|createTable()]]: 创建表
* [[Yiisoft\Db\Migration::renameTable()|renameTable()]]: 重命名表名
* [[Yiisoft\Db\Migration::dropTable()|dropTable()]]: 删除一张表
* [[Yiisoft\Db\Migration::truncateTable()|truncateTable()]]: 清空表中的所有数据
* [[Yiisoft\Db\Migration::addColumn()|addColumn()]]: 加一个字段
* [[Yiisoft\Db\Migration::renameColumn()|renameColumn()]]: 重命名字段名称
* [[Yiisoft\Db\Migration::dropColumn()|dropColumn()]]: 删除一个字段
* [[Yiisoft\Db\Migration::alterColumn()|alterColumn()]]: 修改字段
* [[Yiisoft\Db\Migration::addPrimaryKey()|addPrimaryKey()]]: 添加一个主键
* [[Yiisoft\Db\Migration::dropPrimaryKey()|dropPrimaryKey()]]: 删除一个主键
* [[Yiisoft\Db\Migration::addForeignKey()|addForeignKey()]]: 添加一个外键
* [[Yiisoft\Db\Migration::dropForeignKey()|dropForeignKey()]]: 删除一个外键
* [[Yiisoft\Db\Migration::createIndex()|createIndex()]]: 创建一个索引
* [[Yiisoft\Db\Migration::dropIndex()|dropIndex()]]: 删除一个索引
* [[Yiisoft\Db\Migration::addCommentOnColumn()|addCommentOnColumn()]]: adding comment to column
* [[Yiisoft\Db\Migration::dropCommentFromColumn()|dropCommentFromColumn()]]: dropping comment from column
* [[Yiisoft\Db\Migration::addCommentOnTable()|addCommentOnTable()]]: adding comment to table
* [[Yiisoft\Db\Migration::dropCommentFromTable()|dropCommentFromTable()]]: dropping comment from table

> Tip: [[Yiisoft\Db\Migration]] 并没有提供数据库的查询方法。
  这是因为通常你是不需要去数据库把数据一行一行查出来再显示出来的。
  另外一个原因是你完全可以使用强大的 [Query Builder 查询构建器](db-query-builder.md) 来构建和查询。  

> Note: When manipulating data using a migration you may find that using your [Active Record](db-active-record.md) classes
> for this might be useful because some of the logic is already implemented there. Keep in mind however, that in contrast
> to code written in the migrations, who's nature is to stay constant forever, application logic is subject to change.
> So when using Active Record in migration code, changes to the logic in the Active Record layer may accidentally break
> existing migrations. For this reason migration code should be kept independent from other application logic such
> as Active Record classes.


## 提交迁移 <span id="applying-migrations"></span>

为了将数据库升级到最新的结构，你应该使用如下命令来提交所有新的迁移：

```
yii migrate
```

这条命令会列出迄今为止所有未提交的迁移。如果你确定你需要提交这些迁移，
它将会按照类名当中的时间戳的顺序，一个接着一个的运行每个新的迁移类里面的 `up()` 或者是 `safeUp()` 方法。
如果其中任意一个迁移提交失败了，
那么这条命令将会退出并停止剩下的那些还未执行的迁移。

> Tip: In case you don't have command line at your server you may try [web shell](https://github.com/samdark/yii2-webshell)
> extension.

对于每一个成功提交的迁移，这条命令都会在一个叫做 `migration` 
的数据库表中插入一条包含应用程序成功提交迁移的记录，
该记录将帮助迁移工具判断哪些迁移已经提交，哪些还没有提交。

> Tip: 迁移工具将会自动在数据库当中创建 `migration` 表，
  该数据库是在该命令的 [[Yiisoft\Yii\Console\Controllers\MigrateController::db|db]] 选项当中指定的。
  默认情况下，是由 `db` [application component](structure-application-components.md) 指定的。
  
有时，你可能只需要提交一个或者少数的几个迁移，
你可以使用该命令指定需要执行的条数，而不是执行所有的可用迁移。
例如，如下命令将会尝试提交前三个可用的迁移：

```
yii migrate 3
```

你也可以指定一个特定的迁移，按照如下格式使用 `migrate/to` 命令
来指定数据库应该提交哪一个迁移：

```
yii migrate/to 150101_185401                      # using timestamp to specify the migration 使用时间戳来指定迁移
yii migrate/to "2015-01-01 18:54:01"              # using a string that can be parsed by strtotime() 使用一个可以被 strtotime() 解析的字符串
yii migrate/to m150101_185401_create_news_table   # using full name 使用全名
yii migrate/to 1392853618                         # using UNIX timestamp 使用 UNIX 时间戳
```

如果在指定要提交的迁移前面还有未提交的迁移，那么在执行这个被指定的迁移之前，
这些还未提交的迁移会先被提交。

如果被指定提交的迁移在之前已经被提交过，那么在其之后的那些迁移将会被还原。


## 还原迁移 <span id="reverting-migrations"></span>

你可以使用如下命令来还原其中一个或多个意见被提交过的迁移：

```
yii migrate/down     # revert the most recently applied migration 还原最近一次提交的迁移
yii migrate/down 3   # revert the most 3 recently applied migrations 还原最近三次提交的迁移
```

> Note: 并不是所有的迁移都能被还原。
  尝试还原这类迁移将可能导致报错甚至是终止所有的还原进程。


## 重做迁移 <span id="redoing-migrations"></span>

重做迁移的意思是先还原指定的迁移，然后再次提交。
如下所示：

```
yii migrate/redo        # redo the last applied migration 重做最近一次提交的迁移
yii migrate/redo 3      # redo the last 3 applied migrations 重做最近三次提交的迁移
```

> Note: 如果一个迁移是不能被还原的，那么你将无法对它进行重做。


## 列出迁移 <span id="listing-migrations"></span>

你可以使用如下命令列出那些提交了的或者是还未提交的迁移：

```
yii migrate/history     # 显示最近10次提交的迁移
yii migrate/history 5   # 显示最近5次提交的迁移
yii migrate/history all # 显示所有已经提交过的迁移

yii migrate/new         # 显示前10个还未提交的迁移
yii migrate/new 5       # 显示前5个还未提交的迁移
yii migrate/new all     # 显示所有还未提交的迁移
```


## 修改迁移历史 <span id="modifying-migration-history"></span>

有时候你也许需要简单的标记一下你的数据库已经升级到一个特定的迁移，
而不是实际提交或者是还原迁移。
这个经常会发生在你手动的改变数据库的一个特定状态，而又不想相应的迁移被重复提交。
那么你可以使用如下命令来达到目的：

```
yii migrate/mark 150101_185401                      # 使用时间戳来指定迁移
yii migrate/mark "2015-01-01 18:54:01"              # 使用一个可以被 strtotime() 解析的字符串
yii migrate/mark m150101_185401_create_news_table   # 使用全名
yii migrate/mark 1392853618                         # 使用 UNIX 时间戳
```

该命令将会添加或者删除 `migration` 表当中的某几行数据来表明数据库已经提交到了指定的某个迁移上。
执行这条命令期间不会有任何的迁移会被提交或还原。


## 自定义迁移 <span id="customizing-migrations"></span>

有很多方法可以自定义迁移命令。


### 使用命令行选项 <span id="using-command-line-options"></span>

迁移命令附带了几个命令行选项，可以用来自定义它的行为：

* `interactive`: boolean (默认值为 true)，指定是否以交互模式来运行迁移。
  当被设置为 true 时，在命令执行某些操作前，会提示用户。如果你希望在后台执行该命令，
  那么你应该把它设置成 false。

* `migrationPath`: string (默认值为 `@app/migrations`)，指定存放所有迁移类文件的目录。该选项可以是一个目录的路径，
  也可以是 [路径别名](concept-aliases.md)。需要注意的是指定的目录必选存在，
  否则将会触发一个错误。

* `migrationTable`: string (默认值为 `migration`)，指定用于存储迁移历史信息的数据库表名称。
  如果这张表不存在，那么迁移命令将自动创建这张表。当然你也可以使用这样的字段结构：
  `version varchar(255) primary key, apply_time integer` 来手动创建这张表。

* `db`: string (默认值为 `db`)，指定数据库 [application component](structure-application-components.md) 的 ID。
  它指的是将会被该命令迁移的数据库。

* `templateFile`: string (defaults to `@yii/views/migration.php`)，
  指定生产迁移框架代码类文件的模版文件路径。
  该选项即可以使用文件路径来指定，也可以使用路径 [别名](concept-aliases.md) 来指定。
  该模版文件是一个可以使用预定义变量 `$className` 来获取迁移类名称的 PHP 脚本。

* `generatorTemplateFiles`: array (defaults to `[
        'create_table' => '@yii/views/createTableMigration.php',
        'drop_table' => '@yii/views/dropTableMigration.php',
        'add_column' => '@yii/views/addColumnMigration.php',
        'drop_column' => '@yii/views/dropColumnMigration.php',
        'create_junction' => '@yii/views/createJunctionMigration.php'
  ]`), specifies template files for generating migration code. See "[Generating Migrations](#generating-migrations)"
  for more details.

* `fields`: array of column definition strings used for creating migration code. Defaults to `[]`. The format of each
  definition is `COLUMN_NAME:COLUMN_TYPE:COLUMN_DECORATOR`. For example, `--fields=name:string(12):notNull` produces
  a string column of size 12 which is not null.

如下例子向我们展示了如何使用这些选项：

例如，如果我们需要迁移一个 `forum` 模块，
而该迁移文件放在该模块下的 `migrations` 目录当中，
那么我们可以使用如下命令： 

```
# 在 forum 模块中以非交互模式进行迁移
yii migrate --migrationPath=@app/modules/forum/migrations --interactive=0
```


### 全局配置命令 <span id="configuring-command-globally"></span>

在运行迁移命令的时候每次都要重复的输入一些同样的参数会很烦人，这时候，
你可以选择在应用程序配置当中进行全局配置，一劳永逸：

```php
return [
    'controllerMap' => [
        'migrate' => [
            'class' => 'Yiisoft\Yii\Console\Controllers\MigrateController',
            'migrationTable' => 'backend_migration',
        ],
    ],
];
```

如上所示配置，在每次运行迁移命令的时候，
`backend_migration` 表将会被用来记录迁移历史。
你再也不需要通过 `migrationTable` 命令行参数来指定这张历史纪录表了。


## 迁移多个数据库 <span id="migrating-multiple-databases"></span>

默认情况下，迁移将会提交到由 `db` [application component](structure-application-components.md) 所定义的同一个数据库当中。
如果你需要提交到不同的数据库，你可以像下面那样指定 `db` 命令行选项，

```
yii migrate --db=db2
```

上面的命令将会把迁移提交到 `db2` 数据库当中。

偶尔有限时候你需要提交 *一些* 迁移到一个数据库，而另外一些则提交到另一个数据库。
为了达到这个目的，你应该在实现一个迁移类的时候指定需要用到的数据库组件的 ID ，
如下所示：

```php
<?php

use Yiisoft\Db\Migration;

class m150101_185401_create_news_table extends Migration
{
    public function init()
    {
        $this->db = 'db2';
        parent::init();
    }
}
```

即使你使用 `db` 命令行选项指定了另外一个不同的数据库，上面的迁移还是会被提交到 `db2` 当中。
需要注意的是这个时候迁移的历史信息依然会被记录到 `db` 命令行选项所指定的数据库当中。

如果有多个迁移都使用到了同一个数据库，那么建议你创建一个迁移的基类，里面包含上述的 `init()` 代码。
然后每个迁移类都继承这个基类就可以了。

> Tip: 除了在 [[Yiisoft\Db\Migration::db|db]] 参数当中进行设置以外， 
  你还可以通过在迁移类中创建新的数据库连接来操作不同的数据库。
  然后通过这些连接再使用 [DAO 方法](db-dao.md) 来操作不同的数据库。

另外一个可以让你迁移多个数据库的策略是把迁移存放到不同的目录下，
然后你可以通过如下命令分别对不同的数据库进行迁移：

```
yii migrate --migrationPath=@app/migrations/db1 --db=db1
yii migrate --migrationPath=@app/migrations/db2 --db=db2
...
```

第一条命令将会把 `@app/migrations/db1` 目录下的迁移提交到 `db1` 数据库当中，
第二条命令则会把 `@app/migrations/db2` 下的迁移提交到 `db2` 数据库当中，以此类推。

