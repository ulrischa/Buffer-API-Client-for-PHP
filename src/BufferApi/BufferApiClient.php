<?php

declare(strict_types=1);

namespace BufferApi;

use BufferApi\Exception\BufferApiException;
use BufferApi\Exception\GraphQlRequestException;
use BufferApi\Exception\HttpTransportException;
use BufferApi\Exception\InvalidResponseException;
use BufferApi\Exception\MutationException;
use BufferApi\Http\CurlHttpTransport;
use BufferApi\Http\HttpTransportInterface;

final class BufferApiClient
{
    public function __construct(
        private readonly string $apiToken,
        private readonly ?HttpTransportInterface $transport = null,
        private readonly string $baseUrl = BufferConstants::DEFAULT_BASE_URL
    ) {
        if (trim($this->apiToken) === '') {
            throw new BufferApiException('API token must not be empty.');
        }

        if (trim($this->baseUrl) === '') {
            throw new BufferApiException('Base URL must not be empty.');
        }
    }

    /** @return array<string, mixed> */
    public function getAccount(): array
    {
        $query = <<<'GRAPHQL'
query GetAccount {
  account {
    id
    email
    backupEmail
    avatar
    createdAt
    timezone
    name
    preferences {
      timeFormat
      startOfWeek
      defaultScheduleOption
    }
    connectedApps {
      clientId
      userId
      name
      description
      website
      createdAt
    }
    organizations {
      id
      channelCount
      name
      ownerEmail
      members {
        totalCount
      }
      limits {
        channels
        members
        scheduledPosts
        scheduledThreadsPerChannel
        scheduledStoriesPerChannel
        generateContent
        tags
        ideas
        ideaGroups
        savedReplies
      }
    }
  }
}
GRAPHQL;

        $result = $this->runGraphQl($query);
        return $result['data']['account'] ?? [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getOrganizations(?string $organizationId = null): array
    {
        $query = <<<'GRAPHQL'
query GetOrganizations($filter: OrganizationFilterInput) {
  account {
    organizations(filter: $filter) {
      id
      channelCount
      name
      ownerEmail
      members {
        totalCount
      }
      limits {
        channels
        members
        scheduledPosts
        scheduledThreadsPerChannel
        scheduledStoriesPerChannel
        generateContent
        tags
        ideas
        ideaGroups
        savedReplies
      }
    }
  }
}
GRAPHQL;

        $variables = [
            'filter' => $organizationId !== null ? ['organizationId' => $organizationId] : null,
        ];

        $result = $this->runGraphQl($query, $this->stripNulls($variables));
        return $result['data']['account']['organizations'] ?? [];
    }

    public function getDefaultOrganizationId(): ?string
    {
        $organizations = $this->getOrganizations();
        return $organizations[0]['id'] ?? null;
    }

    /** @return array<string, mixed> */
    public function getChannel(string $channelId): array
    {
        $query = <<<'GRAPHQL'
query GetChannel($input: ChannelInput!) {
  channel(input: $input) {
    id
    allowedActions
    scopes
    avatar
    createdAt
    descriptor
    displayName
    isDisconnected
    isLocked
    isNew
    isQueuePaused
    name
    organizationId
    products
    service
    serviceId
    timezone
    type
    updatedAt
    hasActiveMemberDevice
    externalLink
    postingSchedule {
      day
      times
      paused
    }
    postingGoal {
      goal
      sentCount
      scheduledCount
      status
      periodStart
      periodEnd
    }
    linkShortening {
      isEnabled
      config {
        domain
        name
      }
    }
    metadata {
      __typename
      ... on PinterestMetadata {
        boards {
          id
          serviceId
          name
          url
          description
          avatar
        }
      }
      ... on MastodonMetadata {
        serverUrl
      }
      ... on BlueskyMetadata {
        serverUrl
      }
      ... on FacebookMetadata {
        locationData {
          location
          mapsLink
          googleAccountId
        }
      }
      ... on GoogleBusinessMetadata {
        locationData {
          location
          mapsLink
          googleAccountId
        }
      }
      ... on TwitterMetadata {
        subscriptionType
      }
      ... on LinkedInMetadata {
        shouldShowLinkedinAnalyticsRefreshBanner
      }
      ... on InstagramMetadata {
        defaultToReminders
      }
      ... on TiktokMetadata {
        defaultToReminders
      }
      ... on YoutubeMetadata {
        defaultToReminders
      }
    }
  }
}
GRAPHQL;

        $result = $this->runGraphQl($query, ['input' => ['id' => $channelId]]);
        return $result['data']['channel'] ?? [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getChannels(string $organizationId, ?ChannelsFilter $filter = null): array
    {
        $query = <<<'GRAPHQL'
query GetChannels($input: ChannelsInput!) {
  channels(input: $input) {
    id
    allowedActions
    scopes
    avatar
    createdAt
    descriptor
    displayName
    isDisconnected
    isLocked
    isNew
    isQueuePaused
    name
    organizationId
    products
    service
    serviceId
    timezone
    type
    updatedAt
    hasActiveMemberDevice
    externalLink
    linkShortening {
      isEnabled
      config {
        domain
        name
      }
    }
    metadata {
      __typename
      ... on PinterestMetadata {
        boards {
          id
          serviceId
          name
          url
          description
          avatar
        }
      }
      ... on MastodonMetadata {
        serverUrl
      }
      ... on BlueskyMetadata {
        serverUrl
      }
      ... on FacebookMetadata {
        locationData {
          location
          mapsLink
          googleAccountId
        }
      }
      ... on GoogleBusinessMetadata {
        locationData {
          location
          mapsLink
          googleAccountId
        }
      }
      ... on TwitterMetadata {
        subscriptionType
      }
      ... on LinkedInMetadata {
        shouldShowLinkedinAnalyticsRefreshBanner
      }
      ... on InstagramMetadata {
        defaultToReminders
      }
      ... on TiktokMetadata {
        defaultToReminders
      }
      ... on YoutubeMetadata {
        defaultToReminders
      }
    }
  }
}
GRAPHQL;

        $input = $this->stripNulls([
            'organizationId' => $organizationId,
            'filter' => $filter,
        ]);

        $result = $this->runGraphQl($query, ['input' => $input]);
        return $result['data']['channels'] ?? [];
    }

    /** @return array<string, mixed> */
    public function getPost(string $postId): array
    {
        $query = <<<'GRAPHQL'
query GetPost($input: PostInput!) {
  post(input: $input) {
    id
    ideaId
    status
    via
    schedulingType
    isCustomScheduled
    createdAt
    updatedAt
    dueAt
    sentAt
    text
    externalLink
    channelId
    channelService
    notificationStatus
    allowedActions
    sharedNow
    shareMode
    author {
      id
      email
      avatar
      isDeleted
      name
    }
    error {
      message
      supportUrl
      rawError
    }
    tags {
      id
      color
      name
      isLocked
    }
    notes {
      id
      text
      type
      createdAt
      updatedAt
      allowedActions
      author {
        id
        email
        avatar
        isDeleted
        name
      }
    }
    assets {
      __typename
      id
      type
      mimeType
      source
      thumbnail
      ... on ImageAsset {
        image {
          altText
          width
          height
          isAnimated
          animatedThumbnail
          userTags {
            handle
            x
            y
          }
        }
      }
      ... on VideoAsset {
        video {
          durationMs
          containerFormat
          videoCodec
          frameRate
          videoBitRate
          audioCodec
          rotationDegree
          isTranscodingRequired
          isVideoProcessing
          width
          height
          fileSize
        }
      }
      ... on DocumentAsset {
        document {
          filesize
          numPages
          thumbnails
        }
      }
    }
    metadata {
      __typename
      ... on InstagramPostMetadata {
        type
        firstComment
        link
        shouldShareToFeed
      }
      ... on FacebookPostMetadata {
        type
        firstComment
        title
      }
      ... on LinkedInPostMetadata {
        type
        firstComment
      }
      ... on TwitterPostMetadata {
        type
        threadCount
      }
      ... on PinterestPostMetadata {
        type
        title
        url
        board {
          id
          serviceId
          name
          url
        }
      }
      ... on GoogleBusinessPostMetadata {
        type
        title
      }
      ... on YoutubePostMetadata {
        type
        title
        privacy
        license
        notifySubscribers
        embeddable
        madeForKids
        category {
          categoryId
          title
        }
      }
      ... on MastodonPostMetadata {
        type
        spoilerText
        threadCount
      }
      ... on StartPagePostMetadata {
        type
        link
      }
      ... on ThreadsPostMetadata {
        type
        topic
        locationId
        locationName
        threadCount
      }
      ... on BlueskyPostMetadata {
        type
        threadCount
      }
      ... on TiktokPostMetadata {
        type
        title
      }
    }
    channel {
      id
      name
      service
      serviceId
      displayName
      timezone
      type
      isDisconnected
      isLocked
      externalLink
    }
  }
}
GRAPHQL;

        $result = $this->runGraphQl($query, ['input' => ['id' => $postId]]);
        return $result['data']['post'] ?? [];
    }

    /** @return array<string, mixed> */
    public function getPosts(string $organizationId, ?PostsFilter $filter = null, array $sort = [], int $first = 20, ?string $after = null): array
    {
        $query = <<<'GRAPHQL'
query GetPosts($input: PostsInput!, $first: Int, $after: String) {
  posts(input: $input, first: $first, after: $after) {
    edges {
      cursor
      node {
        id
        ideaId
        status
        via
        schedulingType
        isCustomScheduled
        createdAt
        updatedAt
        dueAt
        sentAt
        text
        externalLink
        channelId
        channelService
        notificationStatus
        allowedActions
        sharedNow
        shareMode
        author {
          id
          email
          avatar
          isDeleted
          name
        }
        error {
          message
          supportUrl
          rawError
        }
        tags {
          id
          color
          name
          isLocked
        }
        assets {
          __typename
          id
          type
          mimeType
          source
          thumbnail
        }
        metadata {
          __typename
        }
        channel {
          id
          name
          service
          serviceId
          displayName
          timezone
          type
          isDisconnected
          isLocked
          externalLink
        }
      }
    }
    pageInfo {
      startCursor
      endCursor
      hasPreviousPage
      hasNextPage
    }
  }
}
GRAPHQL;

        $input = $this->stripNulls([
            'organizationId' => $organizationId,
            'filter' => $filter,
            'sort' => $sort,
        ]);

        $variables = $this->stripNulls([
            'input' => $input,
            'first' => $first,
            'after' => $after,
        ]);

        return $this->runGraphQl($query, $variables)['data']['posts'] ?? [];
    }

    /**
     * @param PostSort[] $sort
     * @return \Generator<int, array<string, mixed>>
     */
    public function iteratePosts(string $organizationId, ?PostsFilter $filter = null, array $sort = [], int $pageSize = 20): \Generator
    {
        $after = null;

        do {
            $page = $this->getPosts($organizationId, $filter, $sort, $pageSize, $after);
            $edges = $page['edges'] ?? [];

            foreach ($edges as $edge) {
                if (isset($edge['node']) && is_array($edge['node'])) {
                    yield $edge['node'];
                }
            }

            $pageInfo = $page['pageInfo'] ?? [];
            $after = $pageInfo['hasNextPage'] ?? false ? ($pageInfo['endCursor'] ?? null) : null;
        } while ($after !== null);
    }

    /** @return array<string, mixed> */
    public function createIdea(CreateIdeaRequest $request): array
    {
        $query = <<<'GRAPHQL'
mutation CreateIdea($input: CreateIdeaInput!) {
  createIdea(input: $input) {
    __typename
    ... on Idea {
      id
      organizationId
      groupId
      position
      createdAt
      updatedAt
      content {
        title
        text
        aiAssisted
        services
        date
        tags {
          id
          color
          name
        }
        media {
          id
          url
          alt
          thumbnailUrl
          type
          size
          source {
            name
            id
            trigger
            author
            authorUrl
          }
        }
      }
    }
    ... on IdeaResponse {
      refreshIdeas
      idea {
        id
        organizationId
        groupId
        position
        createdAt
        updatedAt
        content {
          title
          text
          aiAssisted
          services
          date
          tags {
            id
            color
            name
          }
          media {
            id
            url
            alt
            thumbnailUrl
            type
            size
            source {
              name
              id
              trigger
              author
              authorUrl
            }
          }
        }
      }
    }
    ... on InvalidInputError {
      message
    }
    ... on UnauthorizedError {
      message
    }
    ... on UnexpectedError {
      message
    }
    ... on LimitReachedError {
      message
    }
  }
}
GRAPHQL;

        $result = $this->runGraphQl($query, ['input' => $request->toArray()]);
        $payload = $result['data']['createIdea'] ?? [];

        return $this->normalizeCreateIdeaPayload($payload);
    }

    /** @return array<string, mixed> */
    public function createPost(CreatePostRequest $request): array
    {
        $query = <<<'GRAPHQL'
mutation CreatePost($input: CreatePostInput!) {
  createPost(input: $input) {
    __typename
    ... on PostActionSuccess {
      post {
        id
        ideaId
        status
        via
        schedulingType
        author {
          id
          email
          avatar
          isDeleted
          name
        }
        isCustomScheduled
        createdAt
        updatedAt
        dueAt
        sentAt
        text
        externalLink
        channelId
        channelService
        notificationStatus
        allowedActions
        sharedNow
        shareMode
        tags {
          id
          color
          name
          isLocked
        }
        assets {
          __typename
          id
          type
          mimeType
          source
          thumbnail
        }
        metadata {
          __typename
        }
        channel {
          id
          name
          service
          serviceId
          displayName
          timezone
          type
          isDisconnected
          isLocked
          externalLink
        }
      }
    }
    ... on NotFoundError {
      message
    }
    ... on UnauthorizedError {
      message
    }
    ... on UnexpectedError {
      message
    }
    ... on RestProxyError {
      message
      link
      code
    }
    ... on LimitReachedError {
      message
    }
    ... on InvalidInputError {
      message
    }
  }
}
GRAPHQL;

        $result = $this->runGraphQl($query, ['input' => $request->toArray()]);
        $payload = $result['data']['createPost'] ?? [];

        return $this->normalizeCreatePostPayload($payload);
    }

    /**
     * @param array<string, mixed> $variables
     * @return array<string, mixed>
     */
    private function runGraphQl(string $query, array $variables = []): array
    {
        $transport = $this->transport ?? new CurlHttpTransport();

        $payload = json_encode([
            'query' => $query,
            'variables' => $variables,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($payload === false) {
            throw new InvalidResponseException('Could not encode the GraphQL request payload.');
        }

        $response = $transport->post($this->baseUrl, [
            'Authorization' => 'Bearer ' . $this->apiToken,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ], $payload);

        $body = $response->getBody();
        $decoded = json_decode($body, true);

        if (!is_array($decoded)) {
            throw new InvalidResponseException('Buffer returned invalid JSON.', 0, null, [
                'statusCode' => $response->getStatusCode(),
                'body' => $body,
            ]);
        }

        if ($response->getStatusCode() >= 400) {
            throw new HttpTransportException('Buffer returned HTTP ' . $response->getStatusCode() . '.', 0, null, [
                'statusCode' => $response->getStatusCode(),
                'body' => $decoded,
            ]);
        }

        $errors = $decoded['errors'] ?? [];
        if (is_array($errors) && $errors !== []) {
            $message = 'GraphQL request failed.';
            if (isset($errors[0]['message']) && is_string($errors[0]['message'])) {
                $message = $errors[0]['message'];
            }

            throw new GraphQlRequestException($message, $errors, [
                'response' => $decoded,
            ]);
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function normalizeCreateIdeaPayload(array $payload): array
    {
        $type = $payload['__typename'] ?? 'Unknown';

        if ($type === 'Idea') {
            return [
                'type' => 'Idea',
                'refreshIdeas' => false,
                'idea' => $payload,
            ];
        }

        if ($type === 'IdeaResponse') {
            return [
                'type' => 'IdeaResponse',
                'refreshIdeas' => (bool) ($payload['refreshIdeas'] ?? false),
                'idea' => $payload['idea'] ?? null,
            ];
        }

        $message = $payload['message'] ?? 'Unknown createIdea error.';
        throw new MutationException((string) $message, (string) $type, $payload);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function normalizeCreatePostPayload(array $payload): array
    {
        $type = $payload['__typename'] ?? 'Unknown';

        if ($type === 'PostActionSuccess') {
            return [
                'type' => 'PostActionSuccess',
                'post' => $payload['post'] ?? null,
            ];
        }

        $message = $payload['message'] ?? 'Unknown createPost error.';
        throw new MutationException((string) $message, (string) $type, $payload);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function stripNulls(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if ($value instanceof PayloadSerializable) {
                $payload[$key] = $value->toArray();
                continue;
            }

            if (is_array($value)) {
                $payload[$key] = array_map(
                    static fn ($item) => $item instanceof PayloadSerializable ? $item->toArray() : $item,
                    $value
                );
            }
        }

        return array_filter($payload, static fn ($value): bool => $value !== null);
    }
}
