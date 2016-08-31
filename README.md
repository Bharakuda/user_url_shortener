# URL Shortener is a Symfony 3 App for shortening URL's
 **Note:** *This project is still undergoing a lot of changes*
 
## Installation
Installation is a quick 5 step process:

1. Clone repository
2. Download UserBundle using composer
3. Enable the Bundle
4. Configure your application
5. Create app database

### 1. Clone repository
Create new project directory and execute git clone command inside of it
```console
git clone https://alisa_kopric@bitbucket.org/sightsdigitalteam/url_shortener.git
```

### 2. Download UserBundle using composer
Simply execute following command
```console
composer update
```

information required by the install script:
  - database_host:
  - database_port:
  - database_name:
  - database_username:
  - database_password:
  - mailer_transport:
  - mailer_host:
  - mailer_username:
  - mailer_password:
  - secret: 

### 3. Enable UserBundle module
Register the bundle in AppKernel.php: 

```php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new UserBundle\UserBundle(),
    );
}
```

### 4. Configure your application
Configuration in your config.yml: -> uncomment resource line
> app/config/config.yml
```php
imports:
#    - { resource: "@UserBundle/Resources/config/services.yml" }
```

Configuration in your routing.yml: -> uncomment lines
> app/config/routing.yml
```php
#user:
#    resource: "@UserBundle/Controller/"
#    type:     annotation
#    prefix:   /
```

### 5. Create app database
Prepare your database

This implementation takes care about building the database and creating your schema.
```console
php bin/console doctrine:database:create
php bin/console doctrine:schema:update --force
```
