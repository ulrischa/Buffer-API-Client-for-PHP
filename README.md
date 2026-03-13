# Buffer API Client for PHP

A small PHP library for the public Buffer API that hides GraphQL from application code.

You use regular PHP classes and value objects instead of writing raw GraphQL queries, mutations, variables, and fragments yourself.

## What this package covers

This package wraps the public Buffer operations documented for:

- account retrieval
- organization retrieval
- channel retrieval
- channels retrieval
- post retrieval
- posts retrieval with forward pagination
- idea creation
- post creation

It also includes value objects for the documented input structures such as:

- idea content, tags, media, and groups
- post assets
- post metadata for Instagram, Facebook, LinkedIn, Twitter, Pinterest, Google Business, YouTube, Mastodon, Start Page, Threads, Bluesky, and TikTok
- post filters and sort definitions

## Requirements

- PHP 8.1+
- ext-curl
- ext-json

## Installation

### Option 1: use the package locally

Add the folder as a local package or copy the source tree into your project.

Then load it with Composer:

```json
{
  "autoload": {
    "classmap": [
      "path/to/buffer-api-client-v2/src/"
    ]
  }
}
```

Then run:

```bash
composer dump-autoload
```

### Option 2: use it as a normal Composer library

If you publish this package to your own repository, the existing `composer.json` is ready for that workflow.

## Quick start

```php
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use BufferApi\BufferApiClient;

$client = new BufferApiClient('YOUR_BUFFER_API_TOKEN');

$account = $client->getAccount();
print_r($account);
```

## Basic usage

### 1. Get the default organization ID

```php
$organizationId = $client->getDefaultOrganizationId();

if ($organizationId === null) {
    throw new RuntimeException('No organization found.');
}
```

### 2. Create an idea

```php
use BufferApi\BufferConstants;
use BufferApi\CreateIdeaRequest;
use BufferApi\IdeaContent;
use BufferApi\IdeaMedia;

$ideaContent = (new IdeaContent())
    ->withTitle('LinkedIn content idea')
    ->withText('A short content idea created from PHP.')
    ->withAiAssisted(false)
    ->withServices([BufferConstants::SERVICE_LINKEDIN])
    ->withDate('2026-03-20T09:00:00.000Z')
    ->addMedia(new IdeaMedia(
        url: 'https://example.com/idea-image.jpg',
        type: BufferConstants::MEDIA_TYPE_IMAGE,
        alt: 'Example image'
    ));

$ideaRequest = new CreateIdeaRequest($organizationId, $ideaContent);
$ideaResult = $client->createIdea($ideaRequest);
```

### 3. Create a post

```php
use BufferApi\Assets;
use BufferApi\BufferConstants;
use BufferApi\CreatePostRequest;
use BufferApi\ImageAsset;
use BufferApi\ImageMetadata;
use BufferApi\LinkAttachment;
use BufferApi\PostMetadata;

$assets = (new Assets())
    ->addImage(new ImageAsset(
        url: 'https://example.com/post-image.jpg',
        metadata: new ImageMetadata('Alternative text for the post image')
    ));

$metadata = (new PostMetadata())
    ->forLinkedIn(
        firstComment: 'First comment added through the API client.',
        linkAttachment: new LinkAttachment('https://example.com')
    );

$request = (new CreatePostRequest(
    channelId: 'CHANNEL_ID',
    schedulingType: BufferConstants::SCHEDULING_TYPE_AUTOMATIC,
    mode: BufferConstants::SHARE_MODE_CUSTOM_SCHEDULED
))
    ->withText('This post was created with the PHP client.')
    ->withDueAt('2026-03-21T08:30:00.000Z')
    ->withAssets($assets)
    ->withMetadata($metadata)
    ->withAiAssisted(false);

$result = $client->createPost($request);
```

## Read operations

### Get all organizations

```php
$organizations = $client->getOrganizations();
```

### Get one specific organization

```php
$organizations = $client->getOrganizations('ORGANIZATION_ID');
```

### Get one channel

```php
$channel = $client->getChannel('CHANNEL_ID');
```

### Get all channels for an organization

```php
use BufferApi\ChannelsFilter;
use BufferApi\BufferConstants;

$filter = new ChannelsFilter(
    isLocked: false,
    product: BufferConstants::PRODUCT_PUBLISH
);

$channels = $client->getChannels('ORGANIZATION_ID', $filter);
```

### Get one post

```php
$post = $client->getPost('POST_ID');
```

### Get posts with filters and sort

```php
use BufferApi\BufferConstants;
use BufferApi\DateTimeComparator;
use BufferApi\PostSort;
use BufferApi\PostsFilter;

$filter = (new PostsFilter())
    ->withStatuses([
        BufferConstants::POST_STATUS_DRAFT,
        BufferConstants::POST_STATUS_SCHEDULED,
    ])
    ->withDueAt(new DateTimeComparator(
        start: '2026-03-01T00:00:00.000Z',
        end: '2026-03-31T23:59:59.000Z'
    ));

$sort = [
    PostSort::dueAt(BufferConstants::SORT_DIRECTION_ASC),
];

$page = $client->getPosts('ORGANIZATION_ID', $filter, $sort, 20);
```

### Iterate through all posts

```php
foreach ($client->iteratePosts('ORGANIZATION_ID') as $post) {
    echo $post['id'] . PHP_EOL;
}
```

## Supported metadata builders

The `PostMetadata` value object exposes service-specific methods:

- `forInstagram(...)`
- `forFacebook(...)`
- `forLinkedIn(...)`
- `forTwitter(...)`
- `forPinterest(...)`
- `forGoogleBusiness(...)`
- `forYoutube(...)`
- `forMastodon(...)`
- `forStartPage(...)`
- `forThreads(...)`
- `forBluesky(...)`
- `forTikTok(...)`

You can call one of them depending on the service of the channel you are targeting.

## Error handling

The client throws typed exceptions:

- `BufferApi\Exception\BufferApiException`
- `BufferApi\Exception\HttpTransportException`
- `BufferApi\Exception\InvalidResponseException`
- `BufferApi\Exception\GraphQlRequestException`
- `BufferApi\Exception\MutationException`

Typical example:

```php
use BufferApi\Exception\MutationException;

try {
    $result = $client->createPost($request);
} catch (MutationException $exception) {
    echo $exception->getMessage();
    echo $exception->getPayloadType();
}
```

## Optional PSR-18 transport

By default, the client uses cURL.

If you want to use a PSR-18 compatible HTTP client instead, construct the client with `BufferApi\Http\Psr18HttpTransport`.

```php
use BufferApi\BufferApiClient;
use BufferApi\Http\Psr18HttpTransport;

$transport = new Psr18HttpTransport($httpClient, $requestFactory, $streamFactory);
$client = new BufferApiClient('YOUR_BUFFER_API_TOKEN', $transport);
```

## Notes

- The public Buffer API uses a single GraphQL endpoint.
- This package hides GraphQL, but it does not hide Buffer concepts such as organizations, channels, posts, or service-specific metadata.
- Idea media of type `video` is intentionally rejected because the public Buffer API documentation marks it as unsupported.

## Example file

See `examples/basic_usage.php` for a full end-to-end example.
