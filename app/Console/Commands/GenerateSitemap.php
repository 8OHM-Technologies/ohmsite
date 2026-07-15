<?php

namespace App\Console\Commands;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Generate the XML sitemap';

    public function handle(): void
    {
        $sitemap = Sitemap::create()
            ->add(Url::create('/')
                ->setLastModificationDate(Carbon::yesterday())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                ->setPriority(1.0)
            )
            ->add(Url::create('/demo')
                ->setLastModificationDate(Carbon::yesterday())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                ->setPriority(0.9)
            )
            ->add(Url::create('/services')
                ->setLastModificationDate(Carbon::yesterday())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                ->setPriority(0.8)
            )
            ->add(Url::create('/privacy-policy')
                ->setLastModificationDate(Carbon::yesterday())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_YEARLY)
                ->setPriority(0.3)
            )
            ->add(Url::create('/terms')
                ->setLastModificationDate(Carbon::yesterday())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_YEARLY)
                ->setPriority(0.3)
            )
            ->add(Url::create('/fair-usage')
                ->setLastModificationDate(Carbon::yesterday())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_YEARLY)
                ->setPriority(0.3)
            )
            ->add(Url::create('/refund-cancellation')
                ->setLastModificationDate(Carbon::yesterday())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_YEARLY)
                ->setPriority(0.3)
            );

        // Dynamically add all active products from the database
        Product::all()->each(function (Product $product) use ($sitemap) {
            $sitemap->add(Url::create("/services/{$product->id}")
                ->setLastModificationDate($product->updated_at ?? Carbon::yesterday())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                ->setPriority(0.7)
            );
        });

        $sitemap->writeToFile(public_path('sitemap.xml'));

        $this->info('Sitemap generated successfully.');
    }
}
