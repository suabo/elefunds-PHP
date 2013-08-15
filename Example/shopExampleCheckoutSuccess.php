<?php

/*
 * A sample successful order page
 */
use Lfnds\Facade;
use Lfnds\Template\Shop\CheckoutSuccessConfiguration;

require_once __DIR__ . '/../src/Lfnds/Facade.php';
require_once __DIR__ . '/../src/Lfnds/Template/Shop/CheckoutSuccessConfiguration.php';

$facade = new Facade();
$facade->setConfiguration(new CheckoutSuccessConfiguration());

// Assign current orderId as foreignId
$facade->getConfiguration()->getView()->assign('foreignId', 1234);

?>

<!DOCTYPE HTML >
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title>My awesome Shop</title>
    <?php echo $facade->getPrintableCssTagStrings(); ?>
</head>
<body>
<h2>My awesome Shop</h2>

<!-- Some other HTML here -->

<?php echo $facade->renderTemplate('CheckoutSuccess'); ?>

<!-- Even more HTML here -->

<?php echo $facade->getPrintableJavascriptTagStrings(); ?>

</body>
</html>