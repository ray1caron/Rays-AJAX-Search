# Joomla Coding Best Practices Guide

**Version:** For Joomla 5.x  
**Purpose:** Reference guide for writing secure, maintainable Joomla code  
**Last Updated:** January 2026

---

## Table of Contents

1. [Critical Security Rules](#critical-security-rules)
2. [PHP Coding Standards](#php-coding-standards)
3. [Database Operations](#database-operations)
4. [JavaScript Standards](#javascript-standards)
5. [HTML Best Practices](#html-best-practices)
6. [CSS Guidelines](#css-guidelines)
7. [File Structure & Organization](#file-structure--organization)
8. [Common Patterns](#common-patterns)
9. [Security Checklist](#security-checklist)
10. [Quick Reference](#quick-reference)

---

## Critical Security Rules

### Rule 1: NEVER Use Raw Superglobals

**❌ NEVER DO THIS:**
```php
$id = $_GET['id'];
$name = $_POST['name'];
$data = $_REQUEST['data'];
$cookie = $_COOKIE['value'];
$server = $_SERVER['HTTP_HOST'];
```

**✅ ALWAYS DO THIS:**
```php
$app = Factory::getApplication();
$input = $app->input;

// Get typed input
$id = $input->getInt('id', 0);                    // Integer with default 0
$name = $input->getString('name', '');            // String with default ''
$email = $input->get('email', '', 'email');       // Email filter
$data = $input->get('data', array(), 'array');    // Array
$bool = $input->getBool('flag', false);           // Boolean
$float = $input->getFloat('price', 0.0);          // Float

// Available filters: INT, UINT, FLOAT, BOOL, WORD, ALNUM, CMD, BASE64, STRING, 
// HTML, ARRAY, PATH, USERNAME, EMAIL, URL, TEL, RAW
```

### Rule 2: Always Use JDatabase Methods

**❌ NEVER DO THIS:**
```php
$query = "SELECT * FROM #__table WHERE id = " . $id;
$query = "INSERT INTO #__table (name) VALUES ('" . $name . "')";
```

**✅ ALWAYS DO THIS:**
```php
use Joomla\CMS\Factory;

$db = Factory::getDbo();
$query = $db->getQuery(true);

// SELECT query
$query->select('*')
    ->from($db->quoteName('#__table'))
    ->where($db->quoteName('id') . ' = ' . (int) $id);

// INSERT query
$columns = array('name', 'email', 'created');
$values = array(
    $db->quote($name),
    $db->quote($email),
    $db->quote(Factory::getDate()->toSql())
);

$query->insert($db->quoteName('#__table'))
    ->columns($db->quoteName($columns))
    ->values(implode(',', $values));

$db->setQuery($query);
$db->execute();
```

### Rule 3: Typecast Integers, Quote Strings

```php
// Integers - ALWAYS typecast, NEVER quote
$query->where($db->quoteName('id') . ' = ' . (int) $id);
$query->where($db->quoteName('count') . ' = ' . (int) $count);

// Floats
$query->where($db->quoteName('price') . ' = ' . (float) $price);

// Strings - ALWAYS quote
$query->where($db->quoteName('name') . ' = ' . $db->quote($name));
$query->where($db->quoteName('email') . ' = ' . $db->quote($email));

// NULL values
$query->where($db->quoteName('field') . ' IS NULL');

// LIKE queries - special escaping for wildcards
$search = $db->quote('%' . $db->escape($search, true) . '%', false);
$query->where($db->quoteName('title') . ' LIKE ' . $search);
```

### Rule 4: Define Security Constants

**Every PHP file must start with:**
```php
<?php
/**
 * @package     ComponentName
 * @subpackage  Controllers
 * @copyright   Copyright (C) 2026 Your Name. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

// Your code here
```

**IMPORTANT:** Files containing only PHP should NOT have a closing `?>` tag.

---

## PHP Coding Standards

### Naming Conventions

```php
// Classes - PascalCase
class MyComponentHelper {}
class UserController {}

// Methods and Functions - camelCase
public function getUserData() {}
private function validateInput() {}

// Variables - camelCase
$userId = 123;
$emailAddress = 'user@example.com';

// Constants - UPPER_CASE_SNAKE_CASE
define('MAX_UPLOAD_SIZE', 5242880);
const DEFAULT_LANGUAGE = 'en-GB';

// Private/Protected properties - start with underscore (optional but common)
protected $_db;
private $_userId;
```

### Object-Oriented Requirements

```php
// Use namespaces (Joomla 4+)
namespace MyComponent\Site\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

// Extend Joomla classes
class DisplayController extends BaseController
{
    // Use proper visibility modifiers
    public $publicProperty;
    protected $protectedProperty;
    private $privateProperty;
    
    /**
     * Constructor
     *
     * @param   array  $config  Configuration array
     */
    public function __construct($config = array())
    {
        parent::__construct($config);
        
        // Initialization code
    }
}

// NO global variables - use static properties instead
class Config
{
    protected static $instance = null;
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
```

### Control Structures

```php
// IF statements - space after keyword, braces on new lines
if ($condition)
{
    // Code here
}
elseif ($otherCondition)
{
    // Code here
}
else
{
    // Code here
}

// Alternative syntax for templates (view files)
<?php if ($condition) : ?>
    <div>HTML content</div>
<?php elseif ($otherCondition) : ?>
    <div>Other content</div>
<?php else : ?>
    <div>Default content</div>
<?php endif; ?>

// FOR loops
for ($i = 0; $i < $count; $i++)
{
    // Code here
}

// FOREACH loops
foreach ($items as $key => $value)
{
    // Code here
}

// WHILE loops
while ($condition)
{
    // Code here
}

// SWITCH statements
switch ($value)
{
    case 'option1':
        // Code here
        break;
    
    case 'option2':
        // Code here
        break;
    
    default:
        // Code here
        break;
}
```

### Function and Method Definitions

```php
/**
 * Brief description of function
 *
 * Longer description if needed
 *
 * @param   string  $param1  Description of param1
 * @param   int     $param2  Description of param2
 * @param   array   $param3  Description of param3
 *
 * @return  mixed   Description of return value
 *
 * @throws  Exception  When something goes wrong
 *
 * @since   1.0.0
 */
public function myMethod($param1, $param2 = 0, $param3 = array())
{
    // Validate input
    if (empty($param1))
    {
        throw new InvalidArgumentException('Param1 cannot be empty');
    }
    
    // Method logic
    $result = $this->processData($param1, $param2);
    
    return $result;
}
```

### Error Handling

```php
use Joomla\CMS\Factory;

// Use try-catch blocks
try
{
    $db = Factory::getDbo();
    $query = $db->getQuery(true)
        ->select('*')
        ->from($db->quoteName('#__table'))
        ->where($db->quoteName('id') . ' = ' . (int) $id);
    
    $db->setQuery($query);
    $result = $db->loadObject();
    
    if (!$result)
    {
        throw new RuntimeException('Record not found');
    }
    
    return $result;
}
catch (RuntimeException $e)
{
    $app = Factory::getApplication();
    $app->enqueueMessage($e->getMessage(), 'error');
    
    // Log the error
    Factory::getLog()->add(
        JLog::ERROR,
        $e->getMessage()
    );
    
    return false;
}
```

### Include Files

```php
// Unconditional includes - use require_once
require_once JPATH_COMPONENT . '/helpers/myhelper.php';
require_once JPATH_ADMINISTRATOR . '/components/com_mycomponent/helpers/config.php';

// Conditional includes - use include_once
include_once JPATH_PLUGINS . '/system/myplugin/myplugin.php';

// NO parentheses around the filename
// ❌ require_once(JPATH_COMPONENT . '/file.php');
// ✅ require_once JPATH_COMPONENT . '/file.php';
```

---

## Database Operations

### Getting Database Instance

```php
use Joomla\CMS\Factory;

$db = Factory::getDbo();
$query = $db->getQuery(true);
```

### SELECT Queries

```php
// Basic SELECT
$query->select('*')
    ->from($db->quoteName('#__table'));

// SELECT specific columns
$query->select($db->quoteName(array('id', 'title', 'created')))
    ->from($db->quoteName('#__content'));

// SELECT with alias
$query->select($db->quoteName('a.id', 'article_id'))
    ->from($db->quoteName('#__content', 'a'));

// JOIN
$query->select($db->quoteName(array('a.id', 'a.title', 'c.title'), array('id', 'title', 'category')))
    ->from($db->quoteName('#__content', 'a'))
    ->leftJoin($db->quoteName('#__categories', 'c') . ' ON ' . 
        $db->quoteName('a.catid') . ' = ' . $db->quoteName('c.id'));

// WHERE conditions
$query->where($db->quoteName('published') . ' = 1')
    ->where($db->quoteName('id') . ' = ' . (int) $id);

// OR conditions
$query->where($db->quoteName('state') . ' = 1')
    ->orWhere($db->quoteName('state') . ' = 2');

// WHERE IN
$ids = array(1, 2, 3, 4);
$ids = ArrayHelper::toInteger($ids); // Sanitize array
$query->where($db->quoteName('id') . ' IN (' . implode(',', $ids) . ')');

// ORDER BY
$query->order($db->quoteName('created') . ' DESC');

// LIMIT
$query->setLimit(10, 0); // limit, offset

// Execute and get results
$db->setQuery($query);
$results = $db->loadObjectList(); // Array of objects
$result = $db->loadObject();      // Single object
$value = $db->loadResult();       // Single value
$column = $db->loadColumn();      // Array of single column values
$assoc = $db->loadAssoc();        // Associative array
```

### INSERT Queries

```php
// Method 1: Using arrays
$columns = array('title', 'alias', 'state', 'created');
$values = array(
    $db->quote($title),
    $db->quote($alias),
    1,
    $db->quote(Factory::getDate()->toSql())
);

$query->insert($db->quoteName('#__content'))
    ->columns($db->quoteName($columns))
    ->values(implode(',', $values));

$db->setQuery($query);
$db->execute();

// Get the inserted ID
$insertId = $db->insertid();

// Method 2: Using object
$data = new stdClass();
$data->title = $title;
$data->alias = $alias;
$data->state = 1;
$data->created = Factory::getDate()->toSql();

$db->insertObject('#__content', $data);
```

### UPDATE Queries

```php
// Method 1: Using query builder
$fields = array(
    $db->quoteName('title') . ' = ' . $db->quote($title),
    $db->quoteName('modified') . ' = ' . $db->quote(Factory::getDate()->toSql()),
    $db->quoteName('modified_by') . ' = ' . (int) $userId
);

$query->update($db->quoteName('#__content'))
    ->set($fields)
    ->where($db->quoteName('id') . ' = ' . (int) $id);

$db->setQuery($query);
$db->execute();

// Get affected rows
$affected = $db->getAffectedRows();

// Method 2: Using object (requires primary key)
$data = new stdClass();
$data->id = $id;  // Primary key required
$data->title = $title;
$data->modified = Factory::getDate()->toSql();

$db->updateObject('#__content', $data, 'id');
```

### DELETE Queries

```php
$query->delete($db->quoteName('#__content'))
    ->where($db->quoteName('id') . ' = ' . (int) $id);

$db->setQuery($query);
$db->execute();

// Delete multiple records
$ids = array(1, 2, 3, 4);
$ids = ArrayHelper::toInteger($ids);

$query->delete($db->quoteName('#__content'))
    ->where($db->quoteName('id') . ' IN (' . implode(',', $ids) . ')');

$db->setQuery($query);
$db->execute();
```

### Transactions

```php
try
{
    $db->transactionStart();
    
    // First query
    $query1 = $db->getQuery(true);
    $query1->insert($db->quoteName('#__table1'))
        ->columns($db->quoteName(array('field1', 'field2')))
        ->values($db->quote($value1) . ',' . $db->quote($value2));
    $db->setQuery($query1);
    $db->execute();
    
    // Second query
    $query2 = $db->getQuery(true);
    $query2->update($db->quoteName('#__table2'))
        ->set($db->quoteName('field') . ' = ' . $db->quote($value))
        ->where($db->quoteName('id') . ' = ' . (int) $id);
    $db->setQuery($query2);
    $db->execute();
    
    $db->transactionCommit();
}
catch (Exception $e)
{
    $db->transactionRollback();
    throw $e;
}
```

### Prepared Statements

```php
// For complex queries with many parameters
$query = $db->getQuery(true)
    ->select('*')
    ->from($db->quoteName('#__content'))
    ->where($db->quoteName('id') . ' = :id')
    ->where($db->quoteName('catid') . ' = :catid');

$query->bind(':id', $id, Joomla\Database\ParameterType::INTEGER);
$query->bind(':catid', $catid, Joomla\Database\ParameterType::INTEGER);

$db->setQuery($query);
$result = $db->loadObject();
```

---

## JavaScript Standards

### Naming Conventions

```javascript
// Variables and functions - camelCase
var userName = 'John';
var emailAddress = 'john@example.com';

function getUserData() {
    // Code here
}

// Constants - UPPER_CASE (ES6)
const MAX_UPLOAD_SIZE = 5242880;
const API_ENDPOINT = 'https://api.example.com';

// Private variables (convention) - start with underscore
var _privateVar = 'private';

// Constructors - PascalCase
function UserProfile(name, email) {
    this.name = name;
    this.email = email;
}
```

### Syntax Style

```javascript
// Indentation - 4 spaces (no tabs)
// Semicolons - always use them
// Quotes - single quotes preferred

// Conditional statements
if (condition) {
    // Code here
} else if (otherCondition) {
    // Code here
} else {
    // Code here
}

// For loops
for (var i = 0; i < array.length; i++) {
    // Code here
}

// Functions
function myFunction(param1, param2) {
    // Code here
    return result;
}

// Anonymous functions
var myFunc = function(param) {
    // Code here
};

// Arrow functions (ES6)
const myArrowFunc = (param) => {
    // Code here
};
```

### jQuery Usage

```javascript
// Use jQuery safely
(function($) {
    'use strict';
    
    // DOM ready
    $(document).ready(function() {
        // Your code here
    });
    
    // Or shorter version
    $(function() {
        // Your code here
    });
    
})(jQuery);

// Event handlers
$('#myButton').on('click', function(e) {
    e.preventDefault();
    
    // Handle click
    var data = $(this).data('value');
    console.log(data);
});

// AJAX calls
$.ajax({
    url: 'index.php?option=com_mycomponent&task=getData&format=json',
    type: 'POST',
    dataType: 'json',
    data: {
        id: userId,
        token: Joomla.getOptions('csrf.token')
    },
    success: function(response) {
        console.log(response);
    },
    error: function(xhr, status, error) {
        console.error('Error:', error);
    }
});
```

### Error Handling

```javascript
// Use try-catch for risky operations
try {
    var data = JSON.parse(jsonString);
    processData(data);
} catch (error) {
    console.error('Error parsing JSON:', error);
}

// Check for existence before using
if (typeof myVariable !== 'undefined') {
    // Safe to use myVariable
}

if (window.myFunction) {
    window.myFunction();
}
```

### Comments

```javascript
// Single line comment - space after //

/*
** Multi-line comment
** Each line starts with **
*/

/**
 * Function documentation
 * 
 * @param   {string}  param1  Description of param1
 * @param   {number}  param2  Description of param2
 * @return  {object}  Description of return value
 */
function myFunction(param1, param2) {
    // Code here
}
```

### Best Practices

```javascript
// Don't modify core objects
// ❌ DON'T DO THIS
jQuery.prototype.myFunction = function() {};
Joomla.myProperty = 'value';

// ✅ Create your own namespace
var MyComponent = MyComponent || {};

MyComponent.utils = {
    myFunction: function() {
        // Code here
    }
};

// Always terminate with semicolon
var data = getData();  // Semicolon!

// Use strict mode
(function() {
    'use strict';
    
    // Your code here
})();

// Avoid inline JavaScript in HTML
// ❌ DON'T DO THIS
<button onclick="myFunction()">Click</button>

// ✅ DO THIS
<button id="myButton" class="btn">Click</button>
<script>
document.getElementById('myButton').addEventListener('click', function() {
    myFunction();
});
</script>
```

---

## HTML Best Practices

### Document Structure

```html
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page Title</title>
    
    <link rel="stylesheet" href="//example.com/css/main.css">
    <style>
    body {
        font-size: 16px;
    }
    </style>
</head>
<body>

<main>
    <h1>Main Heading</h1>
    <p>Content here.</p>
</main>

<script src="//code.jquery.com/jquery-latest.js"></script>
<script>
jQuery(document).ready(function($) {
    // JavaScript code
});
</script>

</body>
</html>
```

### Formatting Rules

```html
<!-- All lowercase for elements and attributes -->
<!-- Good -->
<div class="container">
    <img src="image.jpg" alt="Description">
</div>

<!-- Bad -->
<DIV CLASS="container">
    <IMG SRC="image.jpg" ALT="Description">
</DIV>

<!-- Use double quotes for attributes -->
<!-- Good -->
<input type="text" name="username" value="default">

<!-- Bad -->
<input type='text' name='username' value='default'>

<!-- Self-closing elements - no trailing slash -->
<!-- Good -->
<img src="logo.png" alt="Logo">
<br>
<input type="text">

<!-- Bad -->
<img src="logo.png" alt="Logo" />
<br />
<input type="text" />

<!-- Attribute order: class, id, data-*, everything else -->
<a class="btn btn-primary" id="submitBtn" data-action="submit" href="#" title="Submit">
    Submit
</a>

<!-- Indentation - 4 spaces, new line for block elements -->
<div class="parent">
    <ul class="list">
        <li>Item 1</li>
        <li>Item 2</li>
        <li>Item 3</li>
    </ul>
</div>
```

### Semantic HTML

```html
<!-- Use HTML according to its purpose -->
<!-- Good -->
<h1>Main Title</h1>
<h2>Section Title</h2>
<p>This is a paragraph with <a href="/page">a link</a>.</p>
<nav>
    <ul>
        <li><a href="/">Home</a></li>
        <li><a href="/about">About</a></li>
    </ul>
</nav>

<!-- Bad -->
<div class="heading">Main Title</div>
<div class="text">This is a paragraph with <span onclick="goTo()">a link</span>.</div>

<!-- Images must have alt attribute -->
<img src="photo.jpg" alt="Description of photo">

<!-- Avoid inline styles -->
<!-- Bad -->
<p style="color: red; font-size: 16px;">Text</p>

<!-- Good -->
<p class="error-text">Text</p>
```

### Joomla Template Patterns

```php
<!-- View template file (PHP + HTML mix) -->
<?php defined('_JEXEC') or die; ?>

<?php if ($this->items) : ?>
    <div class="items-container">
        <?php foreach ($this->items as $item) : ?>
            <div class="item">
                <h3><?php echo $this->escape($item->title); ?></h3>
                <?php if ($item->image) : ?>
                    <img src="<?php echo $this->escape($item->image); ?>" 
                         alt="<?php echo $this->escape($item->title); ?>">
                <?php endif; ?>
                <p><?php echo $this->escape($item->description); ?></p>
            </div>
        <?php endforeach; ?>
    </div>
<?php else : ?>
    <p class="no-items"><?php echo Text::_('COM_MYCOMPONENT_NO_ITEMS'); ?></p>
<?php endif; ?>
```

---

## CSS Guidelines

### Syntax

```css
/* Use lowercase for selectors and properties */
/* Good */
.my-class {
    color: #333;
    font-size: 16px;
}

/* Bad */
.MY-CLASS {
    Color: #333;
    Font-Size: 16px;
}

/* Indentation - 4 spaces */
.parent {
    margin: 0;
}

    .parent .child {
        padding: 10px;
    }

/* Multi-line formatting */
.selector {
    property1: value1;
    property2: value2;
    property3: value3;
}

/* Order properties alphabetically (optional but recommended) */
.element {
    background: #fff;
    border: 1px solid #ccc;
    color: #333;
    display: block;
    margin: 10px;
    padding: 15px;
}

/* Use shorthand when possible */
/* Good */
margin: 10px 20px 10px 20px;
padding: 10px 20px;

/* Comments */
/* Single line comment */

/*
** Multi-line comment
** describing complex styles
*/
```

### Class Naming

```css
/* Use descriptive, lowercase, hyphen-separated names */
.main-navigation { }
.article-title { }
.user-profile-image { }
.btn-primary { }

/* Avoid abbreviations */
/* Bad */
.usr-prof { }
.art-ttl { }

/* Good */
.user-profile { }
.article-title { }
```

---

## File Structure & Organization

### Component Structure

```
/components/com_mycomponent/
├── mycomponent.php          (Entry point)
├── controller.php           (Base controller)
├── router.php              (SEF routing)
├── /controllers/           (Individual controllers)
│   ├── article.php
│   └── category.php
├── /models/                (Data models)
│   ├── article.php
│   ├── articles.php
│   └── category.php
├── /views/                 (Views)
│   ├── /article/
│   │   ├── view.html.php
│   │   └── /tmpl/
│   │       ├── default.php
│   │       └── default.xml
│   └── /articles/
│       ├── view.html.php
│       └── /tmpl/
│           └── default.php
├── /helpers/               (Helper classes)
│   └── mycomponent.php
└── /sql/                   (SQL files)
    ├── install.mysql.utf8.sql
    └── uninstall.mysql.utf8.sql

/media/com_mycomponent/     (Assets - CSS, JS, Images)
├── /css/
│   └── mycomponent.css
├── /js/
│   └── mycomponent.js
└── /images/
    └── icon.png
```

### Module Structure

```
/modules/mod_mymodule/
├── mod_mymodule.php         (Entry point)
├── mod_mymodule.xml         (Installation manifest)
├── helper.php               (Helper class)
├── /tmpl/                   (Templates)
│   ├── default.php
│   └── alternative.php
└── /sql/                    (SQL files if needed)

/media/mod_mymodule/         (Assets)
├── /css/
├── /js/
└── /images/
```

### Plugin Structure

```
/plugins/system/myplugin/
├── myplugin.php             (Main plugin file)
├── myplugin.xml             (Installation manifest)
└── /sql/                    (SQL files if needed)

/plugins/content/myplugin/
├── myplugin.php
└── myplugin.xml
```

### File Permissions

```bash
# Directories: 755
chmod 755 /path/to/joomla/components/

# PHP files: 644
chmod 644 /path/to/joomla/components/com_mycomponent/mycomponent.php

# Configuration file: 444 (read-only after setup)
chmod 444 /path/to/joomla/configuration.php
```

---

## Common Patterns

### Getting Application Instance

```php
use Joomla\CMS\Factory;

$app = Factory::getApplication();

// Check if site or administrator
$isSite = $app->isClient('site');
$isAdmin = $app->isClient('administrator');

// Get input
$input = $app->input;

// Get user
$user = $app->getIdentity(); // Joomla 4+
// or
$user = Factory::getUser();  // Joomla 3+

// Enqueue messages
$app->enqueueMessage('Success message', 'message');
$app->enqueueMessage('Warning message', 'warning');
$app->enqueueMessage('Error message', 'error');

// Redirect
$app->redirect('index.php?option=com_mycomponent', 'Message here');

// Get session
$session = $app->getSession();
$value = $session->get('key', 'default', 'namespace');
$session->set('key', $value, 'namespace');
```

### Working with Dates

```php
use Joomla\CMS\Factory;
use Joomla\CMS\Date\Date;

// Current date/time
$now = Factory::getDate();
$sqlFormat = $now->toSql();         // Y-m-d H:i:s
$unixTime = $now->toUnix();
$iso8601 = $now->toISO8601();

// Specific date
$date = Factory::getDate('2026-01-15 10:30:00');

// Date math
$tomorrow = Factory::getDate('+1 day');
$nextWeek = Factory::getDate('+1 week');
$lastMonth = Factory::getDate('-1 month');

// Format date
$formatted = $date->format('Y-m-d');
$formatted = $date->format('F j, Y');  // January 15, 2026
```

### Language/Translation

```php
use Joomla\CMS\Language\Text;

// Simple translation
echo Text::_('COM_MYCOMPONENT_TITLE');

// Translation with sprintf
echo Text::sprintf('COM_MYCOMPONENT_ITEMS_COUNT', $count);

// Plural handling
echo Text::plural('COM_MYCOMPONENT_N_ITEMS', $count);

// Alternative text
echo Text::alt('COM_MYCOMPONENT_TITLE', 'Fallback Title');

// JavaScript
Text::script('COM_MYCOMPONENT_JS_MESSAGE');
// In JavaScript: Joomla.Text._('COM_MYCOMPONENT_JS_MESSAGE');
```

### User Management

```php
use Joomla\CMS\Factory;

// Get current user
$user = Factory::getUser();

// Check if logged in
if ($user->guest)
{
    // User is not logged in
}

// Check permissions
if ($user->authorise('core.edit', 'com_mycomponent'))
{
    // User can edit
}

if ($user->authorise('core.admin', 'com_mycomponent'))
{
    // User is administrator
}

// Get user by ID
$user = Factory::getUser($userId);

// User properties
$userId = $user->id;
$userName = $user->name;
$userEmail = $user->email;
$userGroups = $user->groups;

// Load user by email
$db = Factory::getDbo();
$query = $db->getQuery(true)
    ->select('id')
    ->from($db->quoteName('#__users'))
    ->where($db->quoteName('email') . ' = ' . $db->quote($email));
$db->setQuery($query);
$userId = $db->loadResult();
```

### File Upload Handling

```php
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;

$app = Factory::getApplication();
$input = $app->input;

// Get uploaded file
$file = $input->files->get('upload_field');

// Validate
if ($file['error'])
{
    throw new RuntimeException('Upload error: ' . $file['error']);
}

// Check file size (5MB limit)
$maxSize = 5 * 1024 * 1024;
if ($file['size'] > $maxSize)
{
    throw new RuntimeException('File too large');
}

// Check file type
$allowedTypes = array('image/jpeg', 'image/png', 'application/pdf');
if (!in_array($file['type'], $allowedTypes))
{
    throw new RuntimeException('Invalid file type');
}

// Sanitize filename
$filename = File::makeSafe($file['name']);

// Upload directory
$uploadDir = JPATH_ROOT . '/images/uploads';

// Ensure directory exists
if (!Folder::exists($uploadDir))
{
    Folder::create($uploadDir);
}

// Full path
$filepath = $uploadDir . '/' . $filename;

// Move uploaded file
if (!File::upload($file['tmp_name'], $filepath))
{
    throw new RuntimeException('Upload failed');
}
```

### CSRF Protection

```php
// In forms - add token
use Joomla\CMS\Session\Session;

echo HTMLHelper::_('form.token');
// or
<input type="hidden" name="<?php echo Session::getFormToken(); ?>" value="1">

// Check token in controller
Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

// JavaScript AJAX
var token = Joomla.getOptions('csrf.token');
$.ajax({
    data: {
        [token]: 1,
        // other data
    }
});
```

---

## Security Checklist

### Before Deploying Code

- [ ] All user input retrieved via JInput
- [ ] No raw `$_GET`, `$_POST`, `$_REQUEST`, `$_SERVER`, `$_COOKIE`
- [ ] All integers typecast: `(int) $id`
- [ ] All strings quoted: `$db->quote($string)`
- [ ] All database queries use JDatabase methods
- [ ] No hardcoded SQL queries with concatenated variables
- [ ] All file operations validate paths and filenames
- [ ] File uploads check file type, size, and sanitize names
- [ ] CSRF tokens used in all forms
- [ ] Output escaped in templates: `$this->escape($value)`
- [ ] `defined('_JEXEC') or die;` at top of every PHP file
- [ ] No closing `?>` tag in PHP-only files
- [ ] Proper error handling with try-catch blocks
- [ ] No sensitive information in error messages
- [ ] Proper authorization checks: `$user->authorise()`
- [ ] Session data validated before use
- [ ] No arbitrary entry points (direct-access PHP files)
- [ ] File permissions set correctly (755 for dirs, 644 for files)

---

## Quick Reference

### Common Joomla Paths

```php
JPATH_ROOT              // Root directory
JPATH_SITE              // Site root
JPATH_ADMINISTRATOR     // Administrator directory
JPATH_COMPONENT         // Current component directory
JPATH_COMPONENT_SITE    // Component frontend directory
JPATH_COMPONENT_ADMINISTRATOR  // Component backend directory
JPATH_PLUGINS           // Plugins directory
JPATH_CACHE             // Cache directory
JPATH_MANIFESTS         // Installation manifests
```

### Factory Methods

```php
Factory::getApplication()   // Get application instance
Factory::getUser()          // Get current user
Factory::getSession()       // Get session
Factory::getConfig()        // Get configuration
Factory::getDbo()           // Get database object
Factory::getDate()          // Get JDate object
Factory::getDocument()      // Get document object
Factory::getLanguage()      // Get language object
Factory::getMailer()        // Get mailer object
```

### JInput Filters

```php
$input->getInt('var', 0)           // Integer
$input->getFloat('var', 0.0)       // Float
$input->getBool('var', false)      // Boolean
$input->getString('var', '')       // String
$input->getWord('var', '')         // Word (A-Z, a-z)
$input->getCmd('var', '')          // Command (A-Z, a-z, 0-9, ., -, _)
$input->getBase64('var', '')       // Base64
$input->getUsername('var', '')     // Username
$input->get('var', '', 'email')    // Email
$input->get('var', '', 'url')      // URL
$input->get('var', array(), 'array')  // Array
$input->get('var', '', 'html')     // HTML (filtered)
$input->get('var', '', 'raw')      // Raw (unfiltered - use with caution)
```

### Database Query Shortcuts

```php
// Quote name
$db->quoteName('column')
$db->qn('column')  // Alias

// Quote value
$db->quote('value')
$db->q('value')  // Alias

// Null date
$db->getNullDate()  // Returns: 0000-00-00 00:00:00
```

### Common HTML Helpers

```php
use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('form.token')                      // CSRF token
HTMLHelper::_('select.genericlist', $options)    // Dropdown list
HTMLHelper::_('date', $date, $format)            // Format date
HTMLHelper::_('grid.sort', $title, $column)      // Sortable column
HTMLHelper::_('grid.published', $value, $i)      // Published toggle
HTMLHelper::_('link', $url, $text)               // Generate link
```

### Useful Utilities

```php
use Joomla\CMS\Filter\OutputFilter;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Uri\Uri;

// Array sanitization
$ids = ArrayHelper::toInteger($ids);

// Generate alias from string
$alias = OutputFilter::stringURLSafe($title);

// File operations
File::upload($src, $dest);
File::copy($src, $dest);
File::delete($file);
File::move($src, $dest);

// Get base URL
$baseUrl = Uri::base();
$root = Uri::root();
```

---

## Resources

- **Coding Standards**: https://developer.joomla.org/coding-standards.html
- **Security Guidelines**: https://docs.joomla.org/Secure_coding_guidelines
- **API Documentation**: https://api.joomla.org
- **Developer Manual**: https://manual.joomla.org
- **GitHub Repository**: https://github.com/joomla/coding-standards

---

**Remember:** These standards exist to make code secure, maintainable, and consistent. Following them will significantly reduce bugs and security vulnerabilities.
