<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use BufferApi\Assets;
use BufferApi\BufferApiClient;
use BufferApi\BufferConstants;
use BufferApi\CreateIdeaRequest;
use BufferApi\CreatePostRequest;
use BufferApi\IdeaContent;
use BufferApi\IdeaMedia;
use BufferApi\ImageAsset;
use BufferApi\ImageMetadata;
use BufferApi\LinkAttachment;
use BufferApi\PostMetadata;

$apiToken = 'YOUR_BUFFER_API_TOKEN';
$client = new BufferApiClient($apiToken);

$organizationId = $client->getDefaultOrganizationId();
if ($organizationId === null) {
    throw new RuntimeException('No organization found for this Buffer account.');
}

$ideaContent = (new IdeaContent())
    ->withTitle('LinkedIn idea from PHP')
    ->withText('A short content idea created without writing GraphQL manually.')
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

print_r($ideaResult);

$channels = $client->getChannels($organizationId);
$linkedinChannelId = null;

foreach ($channels as $channel) {
    if (($channel['service'] ?? null) === BufferConstants::SERVICE_LINKEDIN) {
        $linkedinChannelId = $channel['id'] ?? null;
        break;
    }
}

if (!is_string($linkedinChannelId) || $linkedinChannelId === '') {
    throw new RuntimeException('No LinkedIn channel found in this organization.');
}

$assets = (new Assets())
    ->addImage(new ImageAsset(
        url: 'https://example.com/post-image.jpg',
        metadata: new ImageMetadata('Alternative text for the post image')
    ));

$postMetadata = (new PostMetadata())
    ->forLinkedIn(
        firstComment: 'First comment added through the API client.',
        linkAttachment: new LinkAttachment('https://example.com')
    );

$postRequest = (new CreatePostRequest(
    channelId: $linkedinChannelId,
    schedulingType: BufferConstants::SCHEDULING_TYPE_AUTOMATIC,
    mode: BufferConstants::SHARE_MODE_CUSTOM_SCHEDULED
))
    ->withText('This post was created with the Buffer PHP client v2.')
    ->withDueAt('2026-03-21T08:30:00.000Z')
    ->withAssets($assets)
    ->withMetadata($postMetadata)
    ->withAiAssisted(false)
    ->withSource('examples/basic_usage.php');

$postResult = $client->createPost($postRequest);

print_r($postResult);
