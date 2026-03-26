# Introduction

Web applications almost always require reading HTTP request data, and converting
them to usable and type-safe in-memory data structures.

![Valinor Logo](https://valinor-php.dev/latest/img/valinor-banner.svg)

[Valinor](https://github.com/CuyZ/Valinor/) is a powerful type-safe serializer,
which simplifies parsing input data, by preventing invalid data from being
submitted.

The goal of mezzio-valinor is to provide an out-of-the-box Valinor installation
with fairly usable defaults, which can be applied inside any Mezzio application
layer that needs to extract typed information from a [PSR-7](http://www.php-fig.org/psr/psr-7/)
`ServerRequestInterface`.
