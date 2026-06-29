# evervault-php

## 0.1.1

### Patch Changes

- aa6fbc0: update crypto scheme

## 0.1.0

### Minor Changes

- 46903c5: The `encrypt` function has been enhanced to accept an optional Data Role. This role, once specified, is associated with the data upon encryption. Data Roles can be created in the Evervault Dashboard (Data Roles section) and provide a mechanism for setting clear rules that dictate how and when data, tagged with that role, can be decrypted.
  evervault.encrypt("hello world!", "allow-all");

### Patch Changes

- 9972dd1: Fixes issue in PHP 7.1 & 7.2 caused by trailing commas
