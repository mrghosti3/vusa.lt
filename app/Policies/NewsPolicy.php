<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Models\News;
use App\Models\User;

use Illuminate\Support\Str;
use App\Enums\ModelEnum;
use App\Services\ModelAuthorizer;
use Illuminate\Auth\Access\HandlesAuthorization;

class NewsPolicy extends ModelPolicy
{
    use HandlesAuthorization;

    public function __construct()
    {
        $this->pluralModelName = Str::plural(ModelEnum::NEWS()->label);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\News  $news
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, News $news, ModelAuthorizer $modelAuthorizer)
    {
        $this->authorizer = $modelAuthorizer;

        if ($this->commonChecker($user, $news, CRUDEnum::READ()->label, $this->pluralModelName, false)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\News  $news
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, News $news, ModelAuthorizer $modelAuthorizer)
    {
        $this->authorizer = $modelAuthorizer;

        if ($this->commonChecker($user, $news, CRUDEnum::UPDATE()->label, $this->pluralModelName, false)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\News  $news
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, News $news, ModelAuthorizer $modelAuthorizer)
    {
        $this->authorizer = $modelAuthorizer;

        if ($this->commonChecker($user, $news, CRUDEnum::DELETE()->label, $this->pluralModelName, false)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\News  $news
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, News $news)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\News  $news
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, News $news)
    {
        //
    }
}
