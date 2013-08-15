PHP Guide - Configuration
=========================

This SDK ships with a very powerful configuration mechanism. It allows you to setup a basic configuration and switch it at
runtime.

Basics
------

If you want to implement a custom configuration, it's a good idea to extend the default configuration. Here's an overview:

![overview](http://yuml.me/diagram/scruffy;dir:LR;/class///%20Cool%20Class%20Diagram,%20[note:ConfigurationInterface]-[BaseConfiguration],%20[BaseConfiguration]-%3E[DefaultConfiguration],%20[DefaultConfiguration]-%3E[YourCustomConfiguration] "ConfigurationOverview")

- the `ConfigurationInterface` is the core for all configuration files. If you are really unsatisfied with the BaseConfiguration you should drop us a mail - or - feel free to implement your own version as the SDK can handle it.

- the `BaseConfiguration` is a reference implementation, that implements algorithms for authentication, separates concerns of other key players and administrates implementations for our models.

- the `DefaultConfiguration` sets up a few options that you can use. For example, it sets the REST engine to *curl* and registers vanilla model implementations for receivers and donations.

> In most scenario's, you're good to go with the default configuration.

> The `DefaultConfiguration` registers its setting in the `$configuration->init()` method, that is automatically called by the facade
> after the configuration is initialized. If you have custom logic yourself, this method is a good place for it.

Configuring your app is as easy as:

```php
<?php
use Lfnds\Configuration\DefaultConfiguration;
use Lfnds\Facade;

$configuration = new DefaultConfiguration();
$configuration->setClientId(1001)
    ->setApiKey('ay3456789gg234561234')
    ->setCountrycode('de');

$facade = new Facade($configuration);
?>
```
    
Another scenario would be to implement a class that extends the DefaultConfiguration:

```php
<?php
    use Lfnds\Configuration\DefaultConfiguration;

    class YourCustomConfiguration extends DefaultConfiguration {
    
        protected $clientId = 1001;
        protected $hashedKey = 'eb85fa24f23b7ade5224a036b39556d65e764653';
        protected $countrycode = 'de';
    }
?>
```

Did you noticed, that we did not provide the apiKey, but the already calculated hashedKey? It'll save us some milliseconds,
as there is no need to calculate it on every request to the API; but the more important reason is that you do not have
to add the API Key into version control.

Advanced
--------

You are free to configure the various files of this SDK yourself. For example, you may want to change the REST engine
to something different than curl. You'd then need to implement the `RestInterface` in your own engine. This can be configured
like this:

```php
require_once __DIR__ . '/YourRestEngine.php';
$configuration->setRestImplementation(new YourRestEngine());
```

The same is true for the Donation and Receiver classes. A common scenario would be to implement the donation or the receiver to
support a persistence layer.

An example for `Doctrine` can be found in our [Shopware module](https://github.com/elefunds/elefunds-Shopware).
