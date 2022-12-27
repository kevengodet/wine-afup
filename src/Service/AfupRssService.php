<?php

namespace App\Service;

use App\Entity\Article;
use App\Repository\ArticleRepository;

final class AfupRssService
{
    private \SimpleXMLElement $feed;
    private ArticleRepository $articles;

    public function __construct(ArticleRepository $articles, string $rssFeedUri = 'https://afup.org/rss.xml')
    {
        $this->articles = $articles;
        $this->feed = simplexml_load_file($rssFeedUri);
    }

    public function hasNewArticlesSince(\DateTimeInterface $sinceDate): bool
    {
        $lastPublicatedAt = new \DateTimeImmutable((string) $this->feed->channel->pubDate);

        return $lastPublicatedAt > $sinceDate;
    }

    /**
     * @param \DateTimeInterface $lastUpdatedAt
     * @return Article[]
     * @throws \Exception
     */
    public function retrieveNewArticlesSince(\DateTimeInterface $lastUpdatedAt): array
    {
        if (!$this->hasNewArticlesSince($lastUpdatedAt)) {
            return [];
        }

        $articles = [];
        foreach ($this->feed->channel->item as $item) {
            $pubDate = new \DateTimeImmutable((string) $item->pubDate);
            if ($pubDate <= $lastUpdatedAt) {
                break;
            }

            $articles[] = $this->createArticleFromRssItem($item);
        }

        return $articles;
    }

    public function importNewArticlesIfRequired(): void
    {
        $lastPubDate = $this->articles->findLastArticle()?->getPubDate();
        if (!$lastPubDate) {
            return;
        }

        if ($this->hasNewArticlesSince($lastPubDate)) {
            foreach ($this->retrieveNewArticlesSince($lastPubDate) as $article) {
                $this->articles->save($article);
            }
        }
    }

    /**
     * @throws \Exception
     */
    public function createArticleFromRssItem(\SimpleXMLElement $item): Article
    {
        $article = new Article;
        $article->setPubDate(new \DateTimeImmutable((string) $item->pubDate));
        $article->setTitle((string) $item->title);
        $article->setBody((string) $item->description);
        $slug = substr(strrchr((string) $item->guid, "/"), 1);
        $article->setSlug($slug);

        return $article;
    }
}
