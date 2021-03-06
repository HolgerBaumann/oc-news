<?php namespace HolgerBaumann\News\Components;

use Cms\Classes\ComponentBase;
use Cms\Classes\Page;
use Lang;
use HolgerBaumann\News\Models\Posts as NewsPost;
use HolgerBaumann\News\Models\Categories as NewsCategory;
use Redirect;

class Posts extends ComponentBase
{
    public $posts;

    public $noPostsMessage;

    public $postPage;

    public $sortOrder;

    public $category;

    public $searchFilter;

    public function componentDetails()
    {
        return [
            'name'        => 'holgerbaumann.news::lang.component.posts',
            'description' => ''
        ];
    }

    public function defineProperties()
    {
        return [
            'pageNumber' => [
                'title'       => 'holgerbaumann.news::lang.settings.pagination_title',
                'description' => 'holgerbaumann.news::lang.settings.pagination_description',
                'type'        => 'string',
                'default'     => '{{ :page }}'
            ],
            'postsPerPage' => [
                'title'             => 'holgerbaumann.news::lang.settings.per_page_title',
                'type'              => 'string',
                'validationPattern' => '^[0-9]+$',
                'validationMessage' => 'holgerbaumann.news::lang.settings.per_page_validation',
                'default'           => '10'
            ],
            'noPostsMessage' => [
                'title'             => 'holgerbaumann.news::lang.settings.no_posts_title',
                'description'       => 'holgerbaumann.news::lang.settings.no_posts_description',
                'type'              => 'string',
                'default'           => Lang::get('holgerbaumann.news::lang.settings.no_posts_found'),
                'showExternalParam' => false
            ],
            'sortOrder' => [
                'title'       => 'holgerbaumann.news::lang.settings.posts_order_title',
                'description' => 'holgerbaumann.news::lang.settings.posts_order_description',
                'type'        => 'dropdown',
                'default'     => 'published_at desc',
                'options'     => [
                    'title asc'         => Lang::get('holgerbaumann.news::lang.sorting.title_asc'),
                    'title desc'        => Lang::get('holgerbaumann.news::lang.sorting.title_desc'),
                    'created_at asc'    => Lang::get('holgerbaumann.news::lang.sorting.created_at_asc'),
                    'created_at desc'   => Lang::get('holgerbaumann.news::lang.sorting.created_at_desc'),
                    'updated_at asc'    => Lang::get('holgerbaumann.news::lang.sorting.updated_at_asc'),
                    'updated_at desc'   => Lang::get('holgerbaumann.news::lang.sorting.updated_at_desc'),
                    'published_at asc'  => Lang::get('holgerbaumann.news::lang.sorting.published_at_asc'),
                    'published_at desc' => Lang::get('holgerbaumann.news::lang.sorting.published_at_desc')
                ]
            ],
            'postFeatured' => [
                'title'       => 'holgerbaumann.news::lang.settings.featured_title',
                'description' => 'holgerbaumann.news::lang.settings.featured_description',
                'type'        => 'dropdown',
                'default'     => 0,
                'options'     => [
                    0 => Lang::get('holgerbaumann.news::lang.settings.list_all'),
                    1 => Lang::get('holgerbaumann.news::lang.settings.list_featured'),
                    2 => Lang::get('holgerbaumann.news::lang.settings.list_notfeatured')
                ]
            ],
            'postTranslated' => [
                'title'       => 'holgerbaumann.news::lang.settings.translated_title',
                'description' => 'holgerbaumann.news::lang.settings.translated_description',
                'default'     => false,
                'type'        => 'checkbox'
            ],
            'categoryFilter' => [
                'title'       => 'holgerbaumann.news::lang.settings.category_filter_title',
                'description' => 'holgerbaumann.news::lang.settings.category_filter_description',
                'type'        => 'string',
                'default'     => ''
            ],
            'postPage' => [
                'title'       => 'holgerbaumann.news::lang.settings.post_title',
                'description' => 'holgerbaumann.news::lang.settings.post_description',
                'type'        => 'dropdown',
                'default'     => 'news/post',
                'group'       => 'holgerbaumann.news::lang.settings.links'
            ],
            'categoryPage' => [
                'title'       => 'holgerbaumann.news::lang.settings.category_page_title',
                'description' => 'holgerbaumann.news::lang.settings.category_page_description',
                'type'        => 'dropdown',
                'default'     => '',
                'group'       => 'holgerbaumann.news::lang.settings.links'
            ]
        ];
    }

    public function getCategoryPageOptions()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    public function getPostPageOptions()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    public function onRun()
    {
        $this->prepareVars();

        $this->category = $this->page['category'] = $this->loadCategory();
        $this->page['currentCategorySlug'] = $this->category ? $this->category->slug : null;
        $this->posts = $this->page['posts'] = $this->listPosts();

        if ($pageNumberParam = $this->paramName('pageNumber')) {
            $currentPage = $this->property('pageNumber');

            if ($currentPage > ($lastPage = $this->posts->lastPage()) && $currentPage > 1) {
                return Redirect::to($this->currentPageUrl([$pageNumberParam => $lastPage]));
            }
        }
    }

    protected function prepareVars()
    {
        $this->pageParam = $this->page['pageParam'] = $this->paramName('pageNumber');
        $this->noPostsMessage = $this->page['noPostsMessage'] = $this->property('noPostsMessage');
        $this->searchFilter = $this->page['searchFilter'] = trim(input('search'));
        /*
         * Page links
         */
        $this->postPage = $this->page['postPage'] = $this->property('postPage');
        $this->categoryPage = $this->page['categoryPage'] = $this->property('categoryPage');
    }

    protected function listPosts()
    {
        $category = $this->category ? $this->category->id : null;

        $posts =  NewsPost::listFrontEnd([
            'page'     => $this->property('pageNumber'),
            'sort'     => $this->property('sortOrder'),
            'perPage'  => $this->property('postsPerPage'),
            'featured' => $this->property('postFeatured'),
            'search'   => $this->searchFilter,
            'isTrans'  => $this->property('postTranslated'),
            'category' => $category
        ]);

        $posts->each(function($post) {
            $post->setUrl($this->postPage, $this->controller);
            if (isset($category)) {
                $post->category->each(function($category) {
                    $category->setUrl($this->categoryPage, $this->controller);
                });
            }
        });

        return $posts;
    }

    protected function loadCategory()
    {
        if (!$slug = $this->property('categoryFilter')) {
            return null;
        }

        $category = new NewsCategory;
        $category = $category->isClassExtendedWith('RainLab.Translate.Behaviors.TranslatableModel')
            ? $category->transWhere('slug', $slug)
            : $category->where('slug', $slug);
        $category = $category->first();

        return $category ?: null;
    }
}
