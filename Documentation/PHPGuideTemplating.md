PHP Guide - Templating
======================

The SDK ships with simple, yet extensive templating support. We have a shop template and are always working hard on implementing
templates for other applications as well.

You can create your own template as well. It's easy.

To get started create a new Folder named `Awesome` below the Template Folder and create a `View.phtml` file in it:

    <div>
        <p>$view['number']</p>
    </div>

> `View` is the default, if you want to name your template differently, you have to add the name as parameter to the
> `$facade->renderTemplate('YourDifferentName')` method.

Create an `AwesomeConfiguration.php` file as well, with the following content:

```php
<?php

use Lfnds\Configuration\DefaultConfiguration;
use Lfnds\View\BaseView;

class AwesomeConfiguration extends DefaultConfiguration {

    public function init() {
        parent::init();

        $this->setView(new BaseView());

        $this->view->setTemplate('Awesome');
                   ->setRenderFile('View.phtml');
        $this->view->assign('number', 42);
    }
}    
```

If you now pass the AwesomeConfiguration to your facade, you are able to render the template. As you can see, everything
you assign to the view is accessible in the `$view` Array of your `View.phtml`.

But let's do some more tricks!

Fire some CSS files and JavaScript files in the Folder `Template/Awesome/Css` and `Template/Awesome/Javascript`. You can assign
them to your template like this (assuming you are inside the configuration's `init()` method). We'll add a CDN version of
jQuery as well (the SDK is smart enough to see the difference).

```php
$this->view->addCssFile('awesome.min.css');
$this->view->addJavascriptFile('https://cdnjs.cloudflare.com/ajax/libs/jquery/2.0.3/jquery.min.js');
$this->view->addJavascriptFile('awesome.jquery.min.js');
```

> It's a good practise to provide minimized versions of your static files.

Lets create a hook to multiply a number by ten.

> Hooks are great version if you need to calculate something based on the numbers given (roundup logic, fetch dependencies
> whatever) and want to hide the complexity from those who implement your application.

Create a file named `AwesomeHooks.php` and save it in `Template/Awesome/Hooks`. Then paste in the following content:

```php
<?php
class AwesomeHooks {
    public static function mulitplyByTen($view, $number) {
        $view->assign('number', $number * 10);
    }
}
```

Now adjust your Configuration file like this:

```php
<?php

class AwesomeConfiguration extends DefaultConfiguration {
    public function init() {
        parent::init();

        $this->setView(new BaseView());
        $this->view->setTemplate('Awesome');
        $this->view->registerAssignHook('number', 'AwesomeHooks', 'mulitplyByTen');
    }
}
```

We have omitted the number but added a hook for it. If the number is assigned from the outside, the hook kicks in and adds
a variable named `numberMultipliedByTen` to the view. If the number is set, it will be available like this:

    <div>
        <p><?php echo $view['number']; ?></p>
        <p><?php echo $view['numberMultipliedByTen']; ?></p>
    </div>

In order for this to work, the number must be set, an example would be:

```php  
$facade = new Facade(new AwesomeConfiguration());
$facade->getConfiguration()->getView()->assign('number', 42);
echo $facade->renderTemplate();
```

If the number is assigned, the multiplied version is assigned as well.

> There's a whole lot more to explore. Be sure to check out the shop template to get an in-depth overview on what is
> possible with this SDK's templating system.





