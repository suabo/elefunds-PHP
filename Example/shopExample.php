<?php


ini_set('display_errors', 1);
/*
 * A sample checkout page
 */
use Lfnds\Example\ShopExampleConfiguration;

require_once __DIR__ . '/../src/Lfnds/Facade.php';
require_once __DIR__ . '/ShopExampleConfiguration.php';

$facade = new Lfnds\Facade();
$facade->setConfiguration(new ShopExampleConfiguration());

// Assign the total at runtime.
//
// Normally this would be coming from the grand total in the checkout process.
// For our example, we'll hard-code the value.
$actualTotal = 960;
$facade->getConfiguration()->getView()->assign('sumExcludingDonation', $actualTotal);

// Define the skin of the module. Currently, the skin is made up of the following
// theme: 'light', 'dark'
// color: 'orange', 'blue', 'green', 'purple'
$facade->getConfiguration()->getView()->assign('skin',
    array(
        'theme' =>  'light',
        'color' =>  '#00efa2'
    )
);


?>

<!DOCTYPE HTML >
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title>My Shop</title>
<?php echo $facade->getPrintableCssTagStrings(); ?>
</head>
<body>
    <h2>My awesome Shop</h2>

    <!-- Lets assume the checkout total is 9.60€ -->
    <div style="margin-left: 780px; margin-bottom: 10px; font-weight: bold;">Total:&nbsp;&nbsp;&nbsp;&nbsp;9.60 €</div>

    <!-- Some other HTML here -->

    <!--
        This will render the template. If you are using a form, then you will have access to the selected
        receivers automatically, because they are all rendered as input fields, so you have them to your
        service upon your next request.

        You can opt to override the template in Template/Shop/View.phtml to fit your needs if you have
        another implementation.
    -->
    <?php echo $facade->renderTemplate(); ?>
    <!-- Even more HTML here -->

    <?php echo $facade->getPrintableJavascriptTagStrings(); ?>
</body>
</html>
