# Testing

Install dependencies:

```
composer install
```

Copy `tests/example-env` to `tests/.env` and update it according to your environment. 

> ⚠️ Ensure that the database you specify in `.env` does not actually contain any data as it will be cleared whenever  tests are run. 


## All tests

Run

```
vendor/bin/codecept run
```

## Unit tests

```
vendor/bin/codecept run unit
```

## Functional tests

```
vendor/bin/codecept run functional
```

## Detailed tests with coverage

```
vendor/bin/codecept run --steps --xml --html --coverage --coverage-xml --coverage-html
```

## Using DDEV

Your can use the provided DDEV configuration to run the tests:

```
ddev start
ddev codecept run --steps --xml --html --coverage --coverage-xml --coverage-html
```

> All the above tests can be run by using the `ddev codecept` command.

