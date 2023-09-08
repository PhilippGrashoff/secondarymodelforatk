# secondarymodelforatk
[![codecov](https://codecov.io/gh/PhilippGrashoff/secondarymodelforatk/branch/master/graph/badge.svg)](https://codecov.io/gh/PhilippGrashoff/secondarymodelforatk)


This repository is an extension for [atk4/data](https://github.com/atk4/data). The use case is as follows: You have a Model which can't exist sensibly on its own, e.g. an email address. Without the link to the person/company/somethingelse it belongs to, its pretty useless.

This library helps you if you have e.g. emails which can belong to several "parent" models, like Person Model and Company model, and each Person and each Company can have several emails.

If you want to store all these emails in the same table, you need to save which Model class and which Model Id each email belongs to.
Example data of "email" table:

```
id   value                model_class                 model_id
1    some@email.com       Your\Namespace\Person       4           <- This email belongs to person with Id 4
2    another@email.com    Your\Namespace\Person       4           <- This one too
3    andmore@email.com    Your\Namespace\Company      2           <- This one belongs to the company with id 2
```

This package helps you set up this with only a few lines of code. Check `tests\testmodels` to see demo code for the example above.


# Installation
The easiest way to use this repository is to add it to your composer.json in the require section:
```json
{
  "require": {
    "philippgrashoff/secondarymodelforatk": "4.0.*"
  }
}
```
# Versioning
The version numbers of this repository correspond with the atk4\data versions. So 4.0.x is compatible with atk4\data 4.0.x and so on.
