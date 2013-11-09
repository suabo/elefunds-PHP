PHP Guide for Shops
===================

The SDK includes an extensive shop template that is currently integrated in a wide range of shops; predominantly running on open source systems like [Shopware](https://github.com/elefunds/elefunds-Shopware)
and [Magento](https://github.com/elefunds/elefunds-Magento).

We recommend looking at our solutions there, as most eCommerce systems operate similarly.

> We appreciate your work and interest in the elefunds API. Whenever you need assistance, found a bug or just want to get in touch, just drop
> us a line: **Christian** (Backend), **David** (Design & Frontend) or **Roland** (Frontend) @elefunds.de


Overview
--------

You don't have to worry about dealing with the API at all, as the process is abstracted by the SDK. The only things you should know are:

- We do not transfer money. Instead, donations are reported to the API; we invoice the sum of donations at the end of the month
- You do not have to create donation receipts or keep track of the donations made in your shop, as we will do the job for you

Implementing for shops basically is a seven step process:

1. Set up a configurable plugin
2. Show the elefunds module in the checkout
3. Add any donations made to the order
4. Forward incoming donations to the API
5. Show the social media share after the checkout
6. Add a short disclaimer regarding the donation to the invoice
7. Observe status changes and report them to the API

The good news is that the essentials of the logic - such as retrieving the receivers from the API, displaying the module and much more is done by the elefunds JavaScript template included in the SDK.

Even better is that most of the remaining server side logic - such as sending donations back to the API and safely analyzing the POST request additions is done by the PHP SDK.

So what's left for you to do? Let's walk through each of the steps...


1. Set up a configurable plugin
-------------------------------

Go ahead and create an extension for the shop of your choice. There are a few things that a shop owner should be able configure. An absolute must have is the `clientId` and an `apiKey`.

> For testing purposes, you can use the **clientId** `1001` with the **apiKey** `ay3456789gg234561234`.

You may as well offer a chance to configure a theme (*dark* or *light*) including a primary color in hexformat (e.g. *#FFFFFF*).


2. Show the elefunds module in the checkout
-------------------------------------------
You need to find a place in the checkout where you want to display the module. We recommend doing it BEFORE the listed positions that make up the overall shopping cart.
If you do so, it'll be trusted-shop compatible.

> In our modules, we allow the shop owner to select the placement of the module (ie. before the order button or above the listed positions).
> It's not recommended, but if you like to do that as well, have a look on how we implemented that in our solutions.

Check the documentation of your shop system to see how you can hook the module into the checkout. The common way to do so is using the shop's templating engine.

In your plugin code, place this logic:

```php
$configuration = new \Lfnds\Template\Shop\CheckoutConfiguration();
$facade = new \Lfnds\Facade($configuration);

$facade->getConfiguration()
    ->setClientId($clientId)
    ->setCountrycode($language)
    ->getView()
    ->assign('skin', array(
            'theme' =>  $theme,
            'color' =>  $color
        )
    )
    ->assign('sumExcludingDonation', $sumExcludingDonation)
    ->assign('formSelector', $footerSelector)
    ->assign('totalSelector', $totalSelector);

$elefundsModule = $facade->renderTemplate();
$elefundsJavascript = $facade->getPrintableJavascriptTagStrings();
$elefundsCss = $facade->getPrintableCssTagStrings();
```

This is configuring the facade based upon some smart defaults, provided by the CheckoutConfiguration class. It uses
these values:

- `$clientId` should be configurable in your extension and is the ID of the client
- `$language` should be the current language that of the user. ATM this should be 'en' or 'de'.
- `$theme` and `$color` are optional, just omit the `skin` assignment if you do not offer an option for that in your extension config
- `$sumExcludingDonation` is the grand total of the basket, it must be an integer and reflect the amount in cent *without* the donation!
- `$formSelector` must be the css selector of the form that is send back to the server, when the user buys.
- `$totalSelector` must be the css selector of the tag that is displaying the total - if the user opts for a donation

The selectors are required in order to add the elefunds variables to the POST submit request and to display the new total, once the user has made a donation.

That's it. Add the `$elefundsModule` string to the place in your checkout where you want to display the module.

`$elefundsJavascript` and `$elefundsCss` are fully qualified *script* and *link* tags that just need to be added to your DOM's head.


3. Add any donations made to the order
----------------------------------------

We are now on the server side. Let's check if the user has made a donation. With the help of the selectors we defined earlier, the data for a donation is now
available in the `$_POST` superglobal. There is a helper class that will help you analyze the request for a donation:

```php
$helper = new \Lfnds\Template\Shop\Helper\RequestHelper();

// Check if a donation has been made
if ($helper->isActiveAndValid()) {

    // Returns the made roundUp in cent (e.g. 195)
    $roundUp = $helper->getRoundUp();

    // Returns the roundup, correctly rounded to two decimals after the dot (e.g. 1.95)
    $roundUpAsFloatString = $helper->getRoundUpAsFloatedString();

    // Returns a validated array of positive integers with the receiver ids (e.g. array(1,2))
    $receiverIds = $helper->getReceiverIds();

    // Returns a validated array of positive integers with all displayed receivers (e.g. array(1,2,3));
    $availableReceiverIds = $helper->getAvailableReceiverIds();

    // Returns a comma and space separated list of all chosen receivers (e.g. "WWF, Ärzte ohne Grenzen")
    $receiverAsString = $helper->getReceiversAsString();

    // The suggestion that the roundup algorithm has made, in cent (e.g. 175)
    $suggestedRoundUp = $helper->getSuggestedRoundUp();

    // Returns TRUE if the user has selected to receive a donation receipt, otherwise FALSE
    $userHasRequestedDonationReceipt = $helper->isDonationReceiptRequested();
}
```

You may not need all available options, but a few are necessary to send us the donation and manipulate the order (if you need additional values, that are not provided, please
contact us so we can add them to the SDK.)

Now we need to add the donation as a position to the order. If done so, it can easily be processed by ERP systems, invoices, outgoing emails and so on.

This process depends on your shop system. In Magento we have created a virtual product that we add to the basket; in Shopware we add a donation item to the order.

> **Note**: the donation must be excluded from all taxes and discounts!


4. Forward incoming donations to the API
----------------------------------------

Now that the donation is made, we add it as **pending**. As you will see in **step 7**, there are different states a donation can have in the API.

In order to report a state change, you must persist the donation in the database.

You can opt to save all donation values in a specific row (like we did in Magento), or just implement the *DonationInterface*
for a persistence backend like Doctrine (like we did in Shopware).

A simple, yet efficient table may look like that (we will have a detailed look on the states in few moments).

| foreign_id  | donation                                       | state                        |
|:-----------:| ---------------------------------------------- |:----------------------------:|
| AB12345     | (serialized instance of Lfnds\Model\Donation)  |  pending                     |
| AB23456     | (serialized instance of Lfnds\Model\Donation)  |  scheduled                   |
| AB34567     | (serialized instance of Lfnds\Model\Donation)  |  scheduled_for_cancellation  |
| AB45678     | (serialized instance of Lfnds\Model\Donation)  |  scheduled_for_completion    |

The process can then be implemented like this:

```php
$configuration = new \Lfnds\Template\Shop\CheckoutSuccessConfiguration();
$helper = new \Lfnds\Template\Shop\Helper\RequestHelper();
$facade = new \Lfnds\Facade($configuration);

$facade->getConfiguration()
    ->setClientId($clientId)
    ->setApiKey($apiKey);     # The api key should have been added to the extension configuration

$newTotal = $originalTotalInCent + $helper->getRoundUp();

$donation = $facade->createDonation()
    // Use the order number as foreign id
    ->setForeignId($orderNumber)
    ->setAmount($helper->getRoundUp())
    ->setSuggestedAmount($helper->getSuggestedRoundUp())
    ->setGrandTotal($newTotal)
    ->setReceiverIds($helper->getReceiverIds())
    ->setAvailableReceiverIds($helper->getAvailableReceiverIds())
    ->setTime(new DateTime());

// Please make sure that - for legal reasons - the user information is only send back to us
// if the user has requested a receipt!
if ($helper->isDonationReceiptRequested()) {
    $donation->setDonator(
        $userEmail,         # the customers email address
        $userFirstName,     # the customers first name
        $userLastName,      # the customers last name
        $userStreetAddress, # the customers street address (with number)
        $userZipCode,       # the customers zip code (as string or integer)
        $userCity,          # the customers city
        $userCountryCode    # the customers country code (e.g. 'de')
    );
}

// Let's add the donation to the API
try {
    $facade->addDonation($donation);
    $state = 'pending';
} catch (Lfnds\Exception\ElefundsCommunicationException $exception) {
    // Something went wrong, we try again in step 7!
    $state = 'scheduled';
}

// You will likely have it a bit more abstracted in your shop, but with pdo ...
$pdo->prepare(
    "INSERT INTO lfnds_donation (foreign_id, donation, state) VALUES (?, ?, ?)"
);
$pdo->execute(
    array(
        $orderNumber, serialize($donation), $state
    )
);
```


5. Show the social media share after the checkout
-------------------------------------------------

The keen observers among you have noticed, that we have instantiated the *Facade* with an instance of the *CheckoutSuccessConfiguration*. The reason is that we have to do some some backend work
for the checkout success site to display the elefunds social media share.

Using the **Facade** instantiated above, we can get away with very little code:

```php
$facade->getConfiguration()
       ->getView()
       ->assign('foreignId', $orderId);

$elefundsShare = $facade->renderTemplate();
$elefundsJavascript = $facade->getPrintableJavascriptTagStrings();
$elefundsCss = $facade->getPrintableCssTagStrings();
```

Choose a nice place on the checkout success page, where you want to display the social media share (have a look on our existing solutions or contact us, if you're unsure)
and add the JavaScript and Css to the header of the page.

> You may wonder, where all the information like receiver name came from in the social media share, when all you provided was the foreignId. As it turns out, the JavaScript persists the information in the users
> session storage, in order to make things easy as possible for you!


6. Add a short disclaimer regarding the donation to the invoice
---------------------------------------------------------------

First of all, we assume that the donation appears on invoices and order emails, because it has been added to the order as additional position in step 3.

However, german legislative requires us to provide a disclaimer regarding the donation on each invoice containing a donation.
In order to comply, we have to add the following sentence to the final invoice that is sent to the customer:

```
// English version
Your donation is processed by the elefunds Foundation gUG which forwards 100% to your chosen charities.

// German version
Die Spende wird vereinnahmt für die elefunds Stiftung gUG und zu 100% an die ausgewählten Organisationen weitergeleitet.
Der Kaufbeleg ersetzt keine Spendenbescheinigung im Sinne des Steuerrechts.
```

The common scenario would be to overwrite the invoice template (as we did in Magento) or hook into the PDF generation (as we did in Shopware). But the solution depends heavily on the system
you are implementing for.


7. Observe status changes and report them to the API
----------------------------------------------------

Woohoo! Only one step to go!

We have three states in the API. You already know the first one **pending**, but there are two more:

- **cancelled**
- **completed**

A donation is cancelled when elefunds can't invoice the donation - e.g. when an order including a donation has been refunded or if
the customer refuses to pay.

A donation is completed when you received the money.

Most shops have internal states that reflect this and all you have to do is to hook into the change of a state and implement
a code like the following (assuming you use PDO with the table described above):

```php
// It does not really matter which configuration we use, but the DefaultConfiguration does not have any overhead
$configuration = new Lfnds\Configuration\DefaultConfiguration();
$facade = new Lfnds\Facade($configuration);

$facade->getConfiguration()
    ->setClientId($clientId)
    ->setApiKey($apiKey);

// We define 4 states in our database:
//
// * pending are donations that are reported to the API and wait for a change of state
// * scheduled will reflect donations that are scheduled to be resent to our API (i.e. a previous push failed)
// * scheduled_for_cancellation will reflect those donations that will be send as cancelled to our api.
// * scheduled_for_completion will reflect those donations that will be send for completion.
//
// The idea behind this is, that we keep track of donations where the reporting to the API fails. This should NEVER
// happen, and we work hard that it won't be needed. However, respecting Murphy's law, we'll make sure that no donation
// will be left behind.

$pdo->prepare(
    "
    SELECT donation, state FROM lfnds_donation
    WHERE foreign_id = ?
    OR state = 'scheduled' OR state = 'scheduled_for_cancellation' OR state = 'scheduled_for_completion'
    "
);
$pdo->execute(array($orderNumber));

$pendingDonations = array();
$cancelledDonations = array();
$completedDonations = array();

// Let's sort the donations by state.
while ($row = $pdo->fetch(PDO::FETCH_ASSOC)) {
    $donation = unserialize($row['donation']);

    switch ($row['state']) {
        case 'pending':
        case 'scheduled':
            $pendingDonations[$donation->getForeignId()] = $donation;
        case 'scheduled_for_cancellation':
            $cancelledDonations[$donation->getForeignId()] = $donation;
        case 'scheduled_for_completion':
            $completedDonations[$donation->getForeignId()] = $donation;
    }
}

$successful = array();
$failedPending = array();
$failedCancelled = array();
$failedCompleted = array();

/*
 * Now we add the donations to the API, respecting their current state.
 * We are keen observers of the API response - in case something goes wrong
 * we put the donations in the respective "scheduled" state.
 */

// Add pending donations
try {
    $facade->addDonations($pendingDonations);
    array_merge($successful, array_keys($pendingDonations));
} catch (\Lfnds\Exception\ElefundsCommunicationException $exception) {
    array_merge($failedPending, array_keys($pendingDonations));
}

// Cancel donations
try {
    $facade->cancelDonations($cancelledDonations);
    array_merge($successful, array_keys($cancelledDonations));
} catch (\Lfnds\Exception\ElefundsCommunicationException $exception) {
    array_merge($failedCancelled, array_keys($cancelledDonations));
}

// Verify donation
 try {
     $facade->completeDonations($completedDonations);
     array_merge($successful, array_keys($completedDonations));
 } catch (\Lfnds\Exception\ElefundsCommunicationException $exception) {
     array_merge($failedCompleted, array_keys($completedDonations));
 }

// Now we have to clean up a bit and add failed states to the database.

$pdo->prepare("DELETE FROM lfnds_donation WHERE foreign_id IN (?)");
$pdo->execute(array($successful));

$pdo->prepare("UPDATE lfnds_donation SET state = ? WHERE foreign_id IN (?)");

// Note that we only hit the database, if there's actually something to do
!empty($failedPending) && $pdo->execute(array('scheduled', $failedPending));
!empty($failedCancelled) && $pdo->execute(array('scheduled_for_cancellation', $failedCancelled));
!empty($failedCompleted) && $pdo->execute(array('scheduled_for_completion', $failedCompleted));
```

Summary
-------

Congratulations, you're done!

A good idea might be to contact the elefunds team (if you haven't already) so we can come together and promote your solution
and assist you on getting the module up and running in a community store. We can also host it via our GitHub account and offer additional support and testing.

Thank you for taking your time, we hope you had fun along the way!
