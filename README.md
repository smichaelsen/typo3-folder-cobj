# TypoScript Content Object FOLDER

## The Problem

Existing TYPO3 TypoScript Content Objects make it hard to load sysfolders:

* The `CONTENT` cObj is restricted to not load pages with doktype 200 and above (sysfolders have 254).
* If you want to use the `RECORDS` cObj you need to know the uid of the sysfolder. 

## The Solution

This extension introduces a new TypoScript cObj called `FOLDER`.

Example to load one folder uid:

    lib.footerNavigationPid = FOLDER
    lib.footerNavigationPid {
      containsModule = tx_myext_footernavigation
      restrictToRootPage = 1
      renderObj = TEXT
      renderObj.field = uid
      limit = 1
    }

This will load the uid of the folders that has `tx_myext_footernavigation` assigned in the `pages.module`
(contains module) field - and is on the first level of the current rootline.

Example to load a list of folder uids:

    lib.footerNavigationPid = FOLDER
    lib.footerNavigationPid {
      containsModule = tx_myext_footernavigation
      renderObj = TEXT
      renderObj.field = uid
      renderObj.wrap = |,
      stdWrap.substring = 0,-1
    }

This will load the uids of all folders that have `tx_myext_footernavigation` assigned in the `pages.module`
(contains module) field. The last trailing comma is removed by `stdWrap.substring = 0,-1`.

## Properties

| property name    | type            | default | description |
|------------------|-----------------|---------|-------------|
| `containsModule` | string/stdWrap  | *empty* | Loads only sysfolders that match the given string with their `module` field. |
| `recursive`      | int/stdWrap     | 0       | Is only applied if `restrictToRootPage` is true and will result in looking for matching sysfolders nested within the current root page. The numeric value of this property provides the depth - how far the pagetree will be resolved. Use only if neccessary as it impacts performance. |
| `renderObj`      | cObj            | *empty* | The cObject used for rendering the loaded sysfolders. |
| `restrictToRootPage` | boolean/stdWrap | false | By default sysfolders will be loaded from all over the page tree. If this is true, sysfolders will only be loaded from the current root page (i.e. first page of the current rootline). See also `recursive`. |
| `doktypes`       | intList/stdWrap | 254     | By default only folders (doktype=254) will be loaded (hence the name). You can provide a comma separated list of allowed doktypes. |
| `limit`          | int/stdWrap     | *empty* | Limits the results of folders being loaded |
| `stdWrap`        | stdWrap         | *empty* | Performs stdWrap operations on the output of the FOLDER content object
