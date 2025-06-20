# Breakdown

A command-line tool for breaking down and analyzing HTTP requests. A web-based version of the tool also [available here](https://picperf.io/request/breakdown).

[![Latest Version on Packagist](https://img.shields.io/packagist/v/picperf/breakdown.svg)](https://packagist.org/packages/picperf/breakdown)
[![Total Downloads](https://img.shields.io/packagist/dt/picperf/breakdown.svg)](https://packagist.org/packages/picperf/breakdown)
[![License](https://img.shields.io/packagist/l/picperf/breakdown.svg)](https://packagist.org/packages/picperf/breakdown)

Breakdown is a CLI tool that helps you analyze HTTP requests, providing detailed information about request headers, response times, and other connection details.

![](./screenshot.png)

## Installation

You can install the package via composer:

```bash
composer global require picperf/breakdown
```

## Usage

```bash
breakdown <url>
```

## Metrics You'll Get

-   time-to-first-byte (TTFB)
-   response size
-   redirects
-   TCP connection duration
-   TLS handshake speed
-   DNS lookup time
-   ...and maybe more!

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
