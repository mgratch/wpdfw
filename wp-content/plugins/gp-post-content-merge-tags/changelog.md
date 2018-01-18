
# Changelog

## 1.0.5

	- Fixed issue where Confirmation URL in Entry details metabox displayed awkwardly when other custom items were included in the metabox. 

## 1.0.4

	- Fixed issue where spaces in the confirmation URL parameters were replaced with underscores.

## 1.0.3

	- Added logging to get_entry() method.

## 1.0.2

	- Added support for processing pre-population merge tags in the [noeid] shortcode.

## 1.0.1

	- Fixed issue where required GF script (gravityforms.js) did not load if another GF script (form_admin.js) was already enqueued. 

## 1.0

	- Added support for "pretty id". When enabled, eid parameter will be a 6 character alphanumeric string.

## 1.0.beta1.7

	- Added support for [noeid] merge tag. Contents are displayed when no entry is available.

## 1.0.beta1.6

	- Added support for not displaying any content within the [eid] merge tag if no entry is found.

## 1.0.beta1.5

    - Fixed error introduced by WordPress 4.7.2 where merge tag selector failed to insert merge tag.

## 1.0.beta1.4

    - Fixed error in FireFox which prevented merge tag selector from loading in Post Edit view.
    - Updated merge tag selector to truncate field labels to 40 characters.

## 1.0.beta1.3

	- Fixed issue where anonymous functions are not supported by earlier PHP versions.

## 1.0.beta1.2

	- Fixed conflict with ACF where merge tag selector on the post edit view was not working.

## 1.0.beta1.1

	- Added support for processing pre-population merge tags without eid parameter