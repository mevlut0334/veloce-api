<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $icon icon class or path
 * @property int $order
 * @property bool $is_active
 * @property bool $show_on_home
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Video> $activeVideos
 * @property-read int|null $active_videos_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Video> $videos
 * @property-read int|null $videos_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category forAdmin()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category forApi()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category forHomePage()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category hasActiveVideos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category showOnHome()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereShowOnHome($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category withActiveVideos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category withActiveVideosCount()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category withAllCounts()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category withVideos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category withVideosCount()
 */
	class Category extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $category_id
 * @property int $position 1 veya 2
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Category $category
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeCategoryButton active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeCategoryButton newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeCategoryButton newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeCategoryButton ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeCategoryButton position(int $position)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeCategoryButton query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeCategoryButton whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeCategoryButton whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeCategoryButton whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeCategoryButton whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeCategoryButton wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeCategoryButton whereUpdatedAt($value)
 */
	class HomeCategoryButton extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $title
 * @property string $content_type
 * @property array<array-key, mixed>|null $content_data
 * @property int $order
 * @property bool $is_active
 * @property int $limit
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeSection active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeSection forHomePage()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeSection minimal()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeSection newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeSection newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeSection ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeSection query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeSection whereContentData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeSection whereContentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeSection whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeSection whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeSection whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeSection whereLimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeSection whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeSection whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeSection whereUpdatedAt($value)
 */
	class HomeSection extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $title
 * @property string|null $subtitle
 * @property string|null $button_text
 * @property string|null $button_link
 * @property string|null $image_path
 * @property int|null $video_id
 * @property int $order
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string|null $image_url
 * @property-read \App\Models\Video|null $video
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeSlider active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeSlider forAdmin()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeSlider forHomePage()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeSlider hasVideo()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeSlider newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeSlider newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeSlider ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeSlider query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeSlider whereButtonLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeSlider whereButtonText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeSlider whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeSlider whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeSlider whereImagePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeSlider whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeSlider whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeSlider whereSubtitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeSlider whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeSlider whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeSlider whereVideoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HomeSlider withVideo()
 */
	class HomeSlider extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $subscription_plan_id
 * @property numeric $amount
 * @property string $currency
 * @property string $payment_method
 * @property string $transaction_id
 * @property string $status
 * @property array<array-key, mixed>|null $payment_details
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\SubscriptionPlan $subscriptionPlan
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment byPaymentMethod(string $method)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment completed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment dateRange(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment failed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment forPlan(int $planId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment forUser(int $userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment lastMonth()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment lastWeek()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment refunded()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment thisMonth()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment thisWeek()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment thisYear()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment today()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePaymentDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereSubscriptionPlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment withRelations()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment yesterday()
 */
	class Payment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property numeric $price
 * @property int $duration_days
 * @property string|null $description
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserSubscription> $activeSubscriptions
 * @property-read int|null $active_subscriptions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment> $payments
 * @property-read int|null $payments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserSubscription> $userSubscriptions
 * @property-read int|null $user_subscriptions_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan forAdmin()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan forApi()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan whereDurationDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan withPaymentStats()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPlan withSubscriptionCounts()
 */
	class SubscriptionPlan extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property bool $is_active
 * @property int $usage_count
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Video> $activeVideos
 * @property-read int|null $active_videos_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Video> $videos
 * @property-read int|null $videos_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag alphabetical()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag forAdmin()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag forApi()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag hasActiveVideos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag popular(int $limit = 20)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag search(string $search)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereUsageCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag withActiveVideosCount()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag withAllCounts()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag withVideos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag withVideosCount()
 */
	class Tag extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string|null $avatar
 * @property int $subscription_type 0=free, 1=premium
 * @property string|null $subscription_starts_at
 * @property string|null $subscription_ends_at
 * @property bool $is_active
 * @property bool $is_admin
 * @property \Illuminate\Support\Carbon|null $last_activity_at
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\UserSubscription|null $activeSubscription
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Video> $favorites
 * @property-read int|null $favorites_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment> $payments
 * @property-read int|null $payments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserPlaylist> $playlists
 * @property-read int|null $playlists_count
 * @property-read \App\Models\UserSubscription|null $subscription
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserSubscription> $userSubscriptions
 * @property-read int|null $user_subscriptions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\VideoView> $views
 * @property-read int|null $views_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User admins()
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User inactive()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User nonSubscribers()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User recentActivity(int $days = 30)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User subscribedThisMonth()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User subscribedThisWeek()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User subscribers()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User subscribersWithData()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User subscriptionExpiredLastMonth()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User subscriptionExpiringSoon(int $days = 30)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastActivityAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereSubscriptionEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereSubscriptionStartsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereSubscriptionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 */
	class User extends \Eloquent implements \Filament\Models\Contracts\FilamentUser {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $video_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Video $video
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserFavorite forUser(int $userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserFavorite forVideo(int $videoId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserFavorite newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserFavorite newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserFavorite query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserFavorite recent(int $limit = 10)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserFavorite whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserFavorite whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserFavorite whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserFavorite whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserFavorite whereVideoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserFavorite withRelations()
 */
	class UserFavorite extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $description
 * @property bool $is_public
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Video> $videos
 * @property-read int|null $videos_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPlaylist forUser(int $userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPlaylist newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPlaylist newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPlaylist private()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPlaylist public()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPlaylist query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPlaylist recentlyUpdated(int $limit = 10)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPlaylist whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPlaylist whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPlaylist whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPlaylist whereIsPublic($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPlaylist whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPlaylist whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPlaylist whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPlaylist withVideoCount()
 */
	class UserPlaylist extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $subscription_plan_id
 * @property string $started_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property string $status
 * @property string|null $payment_method
 * @property string|null $transaction_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $createdBy
 * @property-read \App\Models\SubscriptionPlan|null $plan
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSubscription active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSubscription expired()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSubscription expiringSoon(int $days = 7)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSubscription forUser(int $userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSubscription manual()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSubscription newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSubscription newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSubscription paid()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSubscription query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSubscription startedBetween(\Carbon\Carbon $start, \Carbon\Carbon $end)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSubscription whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSubscription whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSubscription whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSubscription wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSubscription whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSubscription whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSubscription whereSubscriptionPlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSubscription whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSubscription whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSubscription whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSubscription withRelations()
 */
	class UserSubscription extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property string $video_path
 * @property string $thumbnail_path
 * @property int $orientation 0=horizontal, 1=vertical
 * @property bool $is_premium
 * @property int|null $duration seconds
 * @property string|null $resolution Örn: 1920x1080, 1080x1920
 * @property int|null $file_size Dosya boyutu (bytes)
 * @property int $view_count
 * @property int $favorite_count
 * @property bool $is_active
 * @property bool $is_processed Video işleme tamamlandı mı?
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Category> $categories
 * @property-read int|null $categories_count
 * @property-read mixed $duration_human
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $favoritedBy
 * @property-read int|null $favorited_by_count
 * @property-read mixed $file_size_human
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserPlaylist> $playlists
 * @property-read int|null $playlists_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Tag> $tags
 * @property-read int|null $tags_count
 * @property-read mixed $thumbnail_url
 * @property-read mixed $video_url
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\VideoView> $views
 * @property-read int|null $views_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video bySlug(string $slug)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video free()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video horizontal()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video inCategory(int $categoryId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video mostFavorited(int $limit = 10)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video popular(int $limit = 10)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video premium()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video processed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video recent(int $limit = 10)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video search(string $term)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video vertical()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereFavoriteCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereFileSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereIsPremium($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereIsProcessed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereOrientation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereResolution($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereThumbnailPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereVideoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereViewCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video withCounts()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video withRelations()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video withTag(int $tagId)
 */
	class Video extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $video_id
 * @property int|null $user_id
 * @property string|null $ip_address
 * @property int $watch_duration
 * @property bool $is_completed
 * @property \Illuminate\Support\Carbon $viewed_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\User|null $user
 * @property-read \App\Models\Video $video
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoView completed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoView dateRange(string $start, string $end)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoView forUser(int $userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoView forVideo(int $videoId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoView newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoView newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoView query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoView thisMonth()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoView thisWeek()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoView thisYear()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoView today()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoView whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoView whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoView whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoView whereIsCompleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoView whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoView whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoView whereVideoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoView whereViewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoView whereWatchDuration($value)
 */
	class VideoView extends \Eloquent {}
}

