Migratte
========

Executable file `bin/migratte`

```php
#!/usr/bin/env php
<?php

use Semisedlak\Migratte\Migrations\Application;

require __DIR__ . '/../vendor/autoload.php';

(Application::boot())->run();
```