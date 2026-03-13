<?php

declare(strict_types=1);

namespace BufferApi;

use InvalidArgumentException;

interface PayloadSerializable
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}

abstract class PayloadObject implements PayloadSerializable
{
    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    protected function stripNulls(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if ($value instanceof PayloadSerializable) {
                $payload[$key] = $value->toArray();
                continue;
            }

            if (is_array($value)) {
                $payload[$key] = $this->mapArray($value);
            }
        }

        return array_filter(
            $payload,
            static fn ($value): bool => $value !== null
        );
    }

    /**
     * @param array<int|string, mixed> $items
     * @return array<int|string, mixed>
     */
    protected function mapArray(array $items): array
    {
        $result = [];

        foreach ($items as $key => $value) {
            if ($value instanceof PayloadSerializable) {
                $result[$key] = $value->toArray();
                continue;
            }

            if (is_array($value)) {
                $result[$key] = $this->mapArray($value);
                continue;
            }

            if ($value !== null) {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}

final class BufferConstants
{
    public const DEFAULT_BASE_URL = 'https://api.buffer.com';

    public const SERVICE_INSTAGRAM = 'instagram';
    public const SERVICE_FACEBOOK = 'facebook';
    public const SERVICE_TWITTER = 'twitter';
    public const SERVICE_LINKEDIN = 'linkedin';
    public const SERVICE_PINTEREST = 'pinterest';
    public const SERVICE_TIKTOK = 'tiktok';
    public const SERVICE_GOOGLE_BUSINESS = 'googlebusiness';
    public const SERVICE_START_PAGE = 'startPage';
    public const SERVICE_MASTODON = 'mastodon';
    public const SERVICE_YOUTUBE = 'youtube';
    public const SERVICE_THREADS = 'threads';
    public const SERVICE_BLUESKY = 'bluesky';

    public const PRODUCT_ANALYZE = 'analyze';
    public const PRODUCT_ENGAGE = 'engage';
    public const PRODUCT_PUBLISH = 'publish';
    public const PRODUCT_BUFFER = 'buffer';
    public const PRODUCT_START_PAGE = 'startPage';
    public const PRODUCT_COMMENTS = 'comments';

    public const SCHEDULING_TYPE_NOTIFICATION = 'notification';
    public const SCHEDULING_TYPE_AUTOMATIC = 'automatic';

    public const SHARE_MODE_ADD_TO_QUEUE = 'addToQueue';
    public const SHARE_MODE_SHARE_NOW = 'shareNow';
    public const SHARE_MODE_SHARE_NEXT = 'shareNext';
    public const SHARE_MODE_CUSTOM_SCHEDULED = 'customScheduled';
    public const SHARE_MODE_RECOMMENDED_TIME = 'recommendedTime';

    public const MEDIA_TYPE_IMAGE = 'image';
    public const MEDIA_TYPE_GIF = 'gif';
    public const MEDIA_TYPE_VIDEO = 'video';
    public const MEDIA_TYPE_LINK = 'link';
    public const MEDIA_TYPE_DOCUMENT = 'document';
    public const MEDIA_TYPE_UNSUPPORTED = 'unsupported';

    public const POST_STATUS_DRAFT = 'draft';
    public const POST_STATUS_NEEDS_APPROVAL = 'needs_approval';
    public const POST_STATUS_SCHEDULED = 'scheduled';
    public const POST_STATUS_SENDING = 'sending';
    public const POST_STATUS_SENT = 'sent';
    public const POST_STATUS_ERROR = 'error';

    public const POST_TYPE_POST = 'post';
    public const POST_TYPE_REEL = 'reel';
    public const POST_TYPE_STORY = 'story';
    public const POST_TYPE_SHORT = 'short';
    public const POST_TYPE_WHATS_NEW = 'whats_new';
    public const POST_TYPE_OFFER = 'offer';
    public const POST_TYPE_EVENT = 'event';
    public const POST_TYPE_CAROUSEL = 'carousel';
    public const POST_TYPE_GHOST_POST = 'ghost_post';
    public const POST_TYPE_THREAD = 'thread';

    public const SORT_DIRECTION_ASC = 'asc';
    public const SORT_DIRECTION_DESC = 'desc';

    public const YOUTUBE_LICENSE_YOUTUBE = 'youtube';
    public const YOUTUBE_LICENSE_CREATIVE_COMMON = 'creativeCommon';

    public const YOUTUBE_PRIVACY_PUBLIC = 'public';
    public const YOUTUBE_PRIVACY_UNLISTED = 'unlisted';
    public const YOUTUBE_PRIVACY_PRIVATE = 'private';

    private function __construct()
    {
    }
}

final class ChannelsFilter extends PayloadObject
{
    public function __construct(
        private readonly ?bool $isLocked = null,
        private readonly ?string $product = null
    ) {
    }

    public function toArray(): array
    {
        return $this->stripNulls([
            'isLocked' => $this->isLocked,
            'product' => $this->product,
        ]);
    }
}

final class DateTimeComparator extends PayloadObject
{
    public function __construct(
        private readonly ?string $start = null,
        private readonly ?string $end = null
    ) {
    }

    public function toArray(): array
    {
        return $this->stripNulls([
            'start' => $this->start,
            'end' => $this->end,
        ]);
    }
}

final class TagComparator extends PayloadObject
{
    /**
     * @param string[] $tagIds
     */
    public function __construct(
        private readonly array $tagIds = [],
        private readonly bool $includeEmpty = false
    ) {
    }

    public function toArray(): array
    {
        return [
            'in' => array_values($this->tagIds),
            'isEmpty' => $this->includeEmpty,
        ];
    }
}

final class PostSort extends PayloadObject
{
    public function __construct(
        private readonly string $field,
        private readonly string $direction = BufferConstants::SORT_DIRECTION_ASC
    ) {
    }

    public static function dueAt(string $direction = BufferConstants::SORT_DIRECTION_ASC): self
    {
        return new self('dueAt', $direction);
    }

    public static function createdAt(string $direction = BufferConstants::SORT_DIRECTION_ASC): self
    {
        return new self('createdAt', $direction);
    }

    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'direction' => $this->direction,
        ];
    }
}

final class PostsFilter extends PayloadObject
{
    /** @var string[] */
    private array $channelIds = [];
    private ?string $startDate = null;
    private ?string $endDate = null;
    /** @var string[] */
    private array $statuses = [];
    private ?TagComparator $tags = null;
    /** @var string[] */
    private array $tagIds = [];
    private ?DateTimeComparator $dueAt = null;
    private ?DateTimeComparator $createdAt = null;

    /**
     * @param string[] $channelIds
     */
    public function withChannelIds(array $channelIds): self
    {
        $this->channelIds = array_values($channelIds);
        return $this;
    }

    public function withStartDate(string $startDate): self
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function withEndDate(string $endDate): self
    {
        $this->endDate = $endDate;
        return $this;
    }

    /**
     * @param string[] $statuses
     */
    public function withStatuses(array $statuses): self
    {
        $this->statuses = array_values($statuses);
        return $this;
    }

    /**
     * @param string[] $tagIds
     */
    public function withTagIds(array $tagIds): self
    {
        $this->tagIds = array_values($tagIds);
        return $this;
    }

    public function withTagsComparator(TagComparator $tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    public function withDueAt(DateTimeComparator $dueAt): self
    {
        $this->dueAt = $dueAt;
        return $this;
    }

    public function withCreatedAt(DateTimeComparator $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function toArray(): array
    {
        return $this->stripNulls([
            'channelIds' => $this->channelIds,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'status' => $this->statuses,
            'tags' => $this->tags,
            'tagIds' => $this->tagIds,
            'dueAt' => $this->dueAt,
            'createdAt' => $this->createdAt,
        ]);
    }
}

final class Tag extends PayloadObject
{
    public function __construct(
        private readonly string $id,
        private readonly string $name,
        private readonly string $color
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'color' => $this->color,
        ];
    }
}

final class IdeaMediaSource extends PayloadObject
{
    public function __construct(
        private readonly string $name,
        private readonly ?string $id = null,
        private readonly ?string $trigger = null,
        private readonly ?string $author = null,
        private readonly ?string $authorUrl = null
    ) {
    }

    public function toArray(): array
    {
        return $this->stripNulls([
            'name' => $this->name,
            'id' => $this->id,
            'trigger' => $this->trigger,
            'author' => $this->author,
            'authorUrl' => $this->authorUrl,
        ]);
    }
}

final class IdeaMedia extends PayloadObject
{
    public function __construct(
        private readonly string $url,
        private readonly string $type,
        private readonly ?string $alt = null,
        private readonly ?string $thumbnailUrl = null,
        private readonly ?int $size = null,
        private readonly ?IdeaMediaSource $source = null
    ) {
        if ($type === BufferConstants::MEDIA_TYPE_VIDEO) {
            throw new InvalidArgumentException('Idea media type "video" is not supported by the public Buffer API.');
        }
    }

    public function toArray(): array
    {
        return $this->stripNulls([
            'url' => $this->url,
            'type' => $this->type,
            'alt' => $this->alt,
            'thumbnailUrl' => $this->thumbnailUrl,
            'size' => $this->size,
            'source' => $this->source,
        ]);
    }
}

final class IdeaGroup extends PayloadObject
{
    public function __construct(
        private readonly ?string $groupId = null,
        private readonly ?string $placeAfterId = null
    ) {
    }

    public function toArray(): array
    {
        return $this->stripNulls([
            'groupId' => $this->groupId,
            'placeAfterId' => $this->placeAfterId,
        ]);
    }
}

final class IdeaContent extends PayloadObject
{
    private ?string $title = null;
    private ?string $text = null;
    /** @var IdeaMedia[] */
    private array $media = [];
    /** @var Tag[] */
    private array $tags = [];
    private ?bool $aiAssisted = null;
    /** @var string[] */
    private array $services = [];
    private ?string $date = null;

    public function withTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function withText(string $text): self
    {
        $this->text = $text;
        return $this;
    }

    public function addMedia(IdeaMedia $media): self
    {
        $this->media[] = $media;
        return $this;
    }

    public function addTag(Tag $tag): self
    {
        $this->tags[] = $tag;
        return $this;
    }

    public function withAiAssisted(bool $aiAssisted): self
    {
        $this->aiAssisted = $aiAssisted;
        return $this;
    }

    /**
     * @param string[] $services
     */
    public function withServices(array $services): self
    {
        $this->services = array_values($services);
        return $this;
    }

    public function withDate(string $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function toArray(): array
    {
        return $this->stripNulls([
            'title' => $this->title,
            'text' => $this->text,
            'media' => $this->media,
            'tags' => $this->tags,
            'aiAssisted' => $this->aiAssisted,
            'services' => $this->services,
            'date' => $this->date,
        ]);
    }
}

final class CreateIdeaRequest extends PayloadObject
{
    private ?string $cta = null;
    private ?IdeaGroup $group = null;
    private ?string $templateId = null;

    public function __construct(
        private readonly string $organizationId,
        private readonly IdeaContent $content
    ) {
    }

    public function withCta(string $cta): self
    {
        $this->cta = $cta;
        return $this;
    }

    public function withGroup(IdeaGroup $group): self
    {
        $this->group = $group;
        return $this;
    }

    public function withTemplateId(string $templateId): self
    {
        $this->templateId = $templateId;
        return $this;
    }

    public function toArray(): array
    {
        return $this->stripNulls([
            'organizationId' => $this->organizationId,
            'content' => $this->content,
            'cta' => $this->cta,
            'group' => $this->group,
            'templateId' => $this->templateId,
        ]);
    }
}

final class ImageDimensions extends PayloadObject
{
    public function __construct(
        private readonly int $width,
        private readonly int $height
    ) {
    }

    public function toArray(): array
    {
        return [
            'width' => $this->width,
            'height' => $this->height,
        ];
    }
}

final class UserTag extends PayloadObject
{
    public function __construct(
        private readonly string $handle,
        private readonly float $x,
        private readonly float $y
    ) {
    }

    public function toArray(): array
    {
        return [
            'handle' => $this->handle,
            'x' => $this->x,
            'y' => $this->y,
        ];
    }
}

final class ImageMetadata extends PayloadObject
{
    /** @var UserTag[] */
    private array $userTags = [];
    private ?string $animatedThumbnail = null;
    private ?ImageDimensions $dimensions = null;

    public function __construct(private readonly string $altText)
    {
    }

    public function withAnimatedThumbnail(string $animatedThumbnail): self
    {
        $this->animatedThumbnail = $animatedThumbnail;
        return $this;
    }

    public function withDimensions(ImageDimensions $dimensions): self
    {
        $this->dimensions = $dimensions;
        return $this;
    }

    public function addUserTag(UserTag $userTag): self
    {
        $this->userTags[] = $userTag;
        return $this;
    }

    public function toArray(): array
    {
        return $this->stripNulls([
            'altText' => $this->altText,
            'animatedThumbnail' => $this->animatedThumbnail,
            'userTags' => $this->userTags,
            'dimensions' => $this->dimensions,
        ]);
    }
}

final class ImageAsset extends PayloadObject
{
    public function __construct(
        private readonly string $url,
        private readonly ?string $thumbnailUrl = null,
        private readonly ?ImageMetadata $metadata = null
    ) {
    }

    public function toArray(): array
    {
        return $this->stripNulls([
            'url' => $this->url,
            'thumbnailUrl' => $this->thumbnailUrl,
            'metadata' => $this->metadata,
        ]);
    }
}

final class VideoMetadata extends PayloadObject
{
    public function __construct(
        private readonly ?int $thumbnailOffset = null,
        private readonly ?string $title = null
    ) {
    }

    public function toArray(): array
    {
        return $this->stripNulls([
            'thumbnailOffset' => $this->thumbnailOffset,
            'title' => $this->title,
        ]);
    }
}

final class VideoAsset extends PayloadObject
{
    public function __construct(
        private readonly string $url,
        private readonly ?string $thumbnailUrl = null,
        private readonly ?VideoMetadata $metadata = null
    ) {
    }

    public function toArray(): array
    {
        return $this->stripNulls([
            'url' => $this->url,
            'thumbnailUrl' => $this->thumbnailUrl,
            'metadata' => $this->metadata,
        ]);
    }
}

final class DocumentAsset extends PayloadObject
{
    public function __construct(
        private readonly string $url,
        private readonly string $title,
        private readonly string $thumbnailUrl
    ) {
    }

    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'title' => $this->title,
            'thumbnailUrl' => $this->thumbnailUrl,
        ];
    }
}

final class LinkAsset extends PayloadObject
{
    public function __construct(
        private readonly string $url,
        private readonly ?string $title = null,
        private readonly ?string $description = null,
        private readonly ?string $thumbnailUrl = null
    ) {
    }

    public function toArray(): array
    {
        return $this->stripNulls([
            'url' => $this->url,
            'title' => $this->title,
            'description' => $this->description,
            'thumbnailUrl' => $this->thumbnailUrl,
        ]);
    }
}

final class Assets extends PayloadObject
{
    /** @var ImageAsset[] */
    private array $images = [];
    /** @var VideoAsset[] */
    private array $videos = [];
    /** @var DocumentAsset[] */
    private array $documents = [];
    private ?LinkAsset $link = null;

    public function addImage(ImageAsset $image): self
    {
        $this->images[] = $image;
        return $this;
    }

    public function addVideo(VideoAsset $video): self
    {
        $this->videos[] = $video;
        return $this;
    }

    public function addDocument(DocumentAsset $document): self
    {
        $this->documents[] = $document;
        return $this;
    }

    public function withLink(LinkAsset $link): self
    {
        $this->link = $link;
        return $this;
    }

    public function toArray(): array
    {
        return $this->stripNulls([
            'images' => $this->images,
            'videos' => $this->videos,
            'documents' => $this->documents,
            'link' => $this->link,
        ]);
    }
}

final class LinkAttachment extends PayloadObject
{
    public function __construct(private readonly string $url)
    {
    }

    public function toArray(): array
    {
        return ['url' => $this->url];
    }
}

final class ThreadedPost extends PayloadObject
{
    private ?string $text = null;
    private ?Assets $assets = null;

    public function __construct(?string $text = null)
    {
        $this->text = $text;
    }

    public function withText(string $text): self
    {
        $this->text = $text;
        return $this;
    }

    public function withAssets(Assets $assets): self
    {
        $this->assets = $assets;
        return $this;
    }

    public function toArray(): array
    {
        return $this->stripNulls([
            'text' => $this->text,
            'assets' => $this->assets,
        ]);
    }
}

final class AnnotationFacebook extends PayloadObject
{
    public function __construct(
        private readonly string $content,
        private readonly array $indices,
        private readonly string $text,
        private readonly string $url
    ) {
    }

    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'indices' => array_values($this->indices),
            'text' => $this->text,
            'url' => $this->url,
        ];
    }
}

final class AnnotationLinkedIn extends PayloadObject
{
    public function __construct(
        private readonly string $id,
        private readonly string $link,
        private readonly string $entity,
        private readonly string $vanityName,
        private readonly string $localizedName,
        private readonly int $start,
        private readonly int $length
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'link' => $this->link,
            'entity' => $this->entity,
            'vanityName' => $this->vanityName,
            'localizedName' => $this->localizedName,
            'start' => $this->start,
            'length' => $this->length,
        ];
    }
}

final class GoogleBusinessOfferDetails extends PayloadObject
{
    public function __construct(
        private readonly string $title,
        private readonly string $startDate,
        private readonly string $endDate,
        private readonly ?string $code = null,
        private readonly ?string $link = null,
        private readonly ?string $terms = null
    ) {
    }

    public function toArray(): array
    {
        return $this->stripNulls([
            'title' => $this->title,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'code' => $this->code,
            'link' => $this->link,
            'terms' => $this->terms,
        ]);
    }
}

final class GoogleBusinessEventDetails extends PayloadObject
{
    public function __construct(
        private readonly string $title,
        private readonly string $startDate,
        private readonly string $endDate,
        private readonly bool $isFullDayEvent,
        private readonly string $button,
        private readonly ?string $link = null
    ) {
    }

    public function toArray(): array
    {
        return $this->stripNulls([
            'title' => $this->title,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'isFullDayEvent' => $this->isFullDayEvent,
            'button' => $this->button,
            'link' => $this->link,
        ]);
    }
}

final class GoogleBusinessWhatsNewDetails extends PayloadObject
{
    public function __construct(
        private readonly string $button,
        private readonly ?string $link = null
    ) {
    }

    public function toArray(): array
    {
        return $this->stripNulls([
            'button' => $this->button,
            'link' => $this->link,
        ]);
    }
}

final class PostMetadata extends PayloadObject
{
    /** @var array<string, mixed> */
    private array $payload = [];

    /** @param AnnotationFacebook[] $annotations */
    public function forFacebook(string $type = BufferConstants::POST_TYPE_POST, array $annotations = [], ?LinkAttachment $linkAttachment = null, ?string $firstComment = null): self
    {
        $this->payload['facebook'] = $this->stripNulls([
            'type' => $type,
            'annotations' => $annotations,
            'linkAttachment' => $linkAttachment,
            'firstComment' => $firstComment,
        ]);

        return $this;
    }

    /** @param AnnotationLinkedIn[] $annotations */
    public function forLinkedIn(array $annotations = [], ?string $firstComment = null, ?LinkAttachment $linkAttachment = null): self
    {
        $this->payload['linkedin'] = $this->stripNulls([
            'annotations' => $annotations,
            'firstComment' => $firstComment,
            'linkAttachment' => $linkAttachment,
        ]);

        return $this;
    }

    public function forInstagram(
        string $type = BufferConstants::POST_TYPE_POST,
        ?string $firstComment = null,
        ?string $link = null,
        ?string $geolocationId = null,
        ?string $geolocationText = null,
        bool $shouldShareToFeed = true,
        ?string $stickerText = null,
        ?string $stickerMusic = null,
        ?string $stickerProducts = null,
        ?string $stickerTopics = null,
        ?string $stickerOther = null
    ): self {
        $geolocation = ($geolocationId !== null || $geolocationText !== null)
            ? $this->stripNulls(['id' => $geolocationId, 'text' => $geolocationText])
            : null;

        $stickerFields = ($stickerText !== null || $stickerMusic !== null || $stickerProducts !== null || $stickerTopics !== null || $stickerOther !== null)
            ? $this->stripNulls([
                'text' => $stickerText,
                'music' => $stickerMusic,
                'products' => $stickerProducts,
                'topics' => $stickerTopics,
                'other' => $stickerOther,
            ])
            : null;

        $this->payload['instagram'] = $this->stripNulls([
            'type' => $type,
            'firstComment' => $firstComment,
            'link' => $link,
            'geolocation' => $geolocation,
            'shouldShareToFeed' => $shouldShareToFeed,
            'stickerFields' => $stickerFields,
        ]);

        return $this;
    }

    /** @param ThreadedPost[] $thread */
    public function forTwitter(?string $retweetId = null, array $thread = []): self
    {
        $this->payload['twitter'] = $this->stripNulls([
            'retweet' => $retweetId !== null ? ['id' => $retweetId] : null,
            'thread' => $thread,
        ]);

        return $this;
    }

    public function forPinterest(string $boardServiceId, ?string $title = null, ?string $url = null): self
    {
        $this->payload['pinterest'] = $this->stripNulls([
            'boardServiceId' => $boardServiceId,
            'title' => $title,
            'url' => $url,
        ]);

        return $this;
    }

    public function forGoogleBusiness(
        string $type,
        ?string $title = null,
        ?GoogleBusinessOfferDetails $detailsOffer = null,
        ?GoogleBusinessEventDetails $detailsEvent = null,
        ?GoogleBusinessWhatsNewDetails $detailsWhatsNew = null
    ): self {
        $this->payload['google'] = $this->stripNulls([
            'type' => $type,
            'title' => $title,
            'detailsOffer' => $detailsOffer,
            'detailsEvent' => $detailsEvent,
            'detailsWhatsNew' => $detailsWhatsNew,
        ]);

        return $this;
    }

    public function forYoutube(
        string $title,
        string $categoryId,
        ?string $privacy = null,
        ?string $license = null,
        ?bool $notifySubscribers = null,
        ?bool $embeddable = null,
        ?bool $madeForKids = null
    ): self {
        $this->payload['youtube'] = $this->stripNulls([
            'title' => $title,
            'categoryId' => $categoryId,
            'privacy' => $privacy,
            'license' => $license,
            'notifySubscribers' => $notifySubscribers,
            'embeddable' => $embeddable,
            'madeForKids' => $madeForKids,
        ]);

        return $this;
    }

    /** @param ThreadedPost[] $thread */
    public function forMastodon(array $thread = [], ?string $spoilerText = null): self
    {
        $this->payload['mastodon'] = $this->stripNulls([
            'thread' => $thread,
            'spoilerText' => $spoilerText,
        ]);

        return $this;
    }

    public function forStartPage(?string $link = null): self
    {
        $this->payload['startPage'] = $this->stripNulls([
            'link' => $link,
        ]);

        return $this;
    }

    /** @param ThreadedPost[] $thread */
    public function forThreads(?string $type = null, array $thread = [], ?LinkAttachment $linkAttachment = null, ?string $topic = null, ?string $locationId = null, ?string $locationName = null): self
    {
        $this->payload['threads'] = $this->stripNulls([
            'type' => $type,
            'thread' => $thread,
            'linkAttachment' => $linkAttachment,
            'topic' => $topic,
            'locationId' => $locationId,
            'locationName' => $locationName,
        ]);

        return $this;
    }

    /** @param ThreadedPost[] $thread */
    public function forBluesky(array $thread = [], ?LinkAttachment $linkAttachment = null): self
    {
        $this->payload['bluesky'] = $this->stripNulls([
            'thread' => $thread,
            'linkAttachment' => $linkAttachment,
        ]);

        return $this;
    }

    public function forTikTok(?string $title = null): self
    {
        $this->payload['tiktok'] = $this->stripNulls([
            'title' => $title,
        ]);

        return $this;
    }

    public function toArray(): array
    {
        return $this->stripNulls($this->payload);
    }
}

final class CreatePostRequest extends PayloadObject
{
    private ?string $ideaId = null;
    private ?string $draftId = null;
    private ?string $dueAt = null;
    private ?string $text = null;
    private ?PostMetadata $metadata = null;
    /** @var string[] */
    private array $tagIds = [];
    private ?Assets $assets = null;
    private ?string $source = null;
    private ?bool $aiAssisted = null;
    private ?bool $saveToDraft = null;

    public function __construct(
        private readonly string $channelId,
        private readonly string $schedulingType,
        private readonly string $mode
    ) {
    }

    public function fromIdea(string $ideaId): self
    {
        $this->ideaId = $ideaId;
        return $this;
    }

    public function fromDraft(string $draftId): self
    {
        $this->draftId = $draftId;
        return $this;
    }

    public function withDueAt(string $dueAt): self
    {
        $this->dueAt = $dueAt;
        return $this;
    }

    public function withText(string $text): self
    {
        $this->text = $text;
        return $this;
    }

    public function withMetadata(PostMetadata $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    /** @param string[] $tagIds */
    public function withTagIds(array $tagIds): self
    {
        $this->tagIds = array_values($tagIds);
        return $this;
    }

    public function withAssets(Assets $assets): self
    {
        $this->assets = $assets;
        return $this;
    }

    public function withSource(string $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function withAiAssisted(bool $aiAssisted): self
    {
        $this->aiAssisted = $aiAssisted;
        return $this;
    }

    public function saveToDraft(bool $saveToDraft = true): self
    {
        $this->saveToDraft = $saveToDraft;
        return $this;
    }

    public function toArray(): array
    {
        return $this->stripNulls([
            'ideaId' => $this->ideaId,
            'draftId' => $this->draftId,
            'schedulingType' => $this->schedulingType,
            'dueAt' => $this->dueAt,
            'text' => $this->text,
            'metadata' => $this->metadata,
            'channelId' => $this->channelId,
            'tagIds' => $this->tagIds,
            'assets' => $this->assets,
            'mode' => $this->mode,
            'source' => $this->source,
            'aiAssisted' => $this->aiAssisted,
            'saveToDraft' => $this->saveToDraft,
        ]);
    }
}
