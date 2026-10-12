<?php

namespace App\Command;

use App\Entity\Brand;
use App\Entity\Category;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[AsCommand(
    name: 'app:seed-products',
    description: 'Seed the database with sample product data for CatalogIQ'
)]
class SeedProductsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $slugger = new AsciiSlugger();

        $io->title('🌱 Seeding CatalogIQ Database');

        // --- Categories ---
        $categoriesData = [
            ['name' => 'Smartphones', 'description' => 'Mobile phones and smartphones'],
            ['name' => 'Laptops', 'description' => 'Notebooks and laptops'],
            ['name' => 'Headphones', 'description' => 'Earphones, headphones, and earbuds'],
            ['name' => 'Tablets', 'description' => 'Tablets and e-readers'],
            ['name' => 'Smartwatches', 'description' => 'Smart watches and fitness trackers'],
            ['name' => 'Cameras', 'description' => 'Digital cameras and action cameras'],
            ['name' => 'Speakers', 'description' => 'Bluetooth and smart speakers'],
            ['name' => 'Gaming', 'description' => 'Gaming consoles and accessories'],
            ['name' => 'Accessories', 'description' => 'Phone cases, cables, and accessories'],
            ['name' => 'Monitors', 'description' => 'Computer monitors and displays'],
        ];

        $io->section('Creating categories...');
        $categories = [];
        foreach ($categoriesData as $data) {
            $category = new Category();
            $category->setName($data['name']);
            $category->setSlug(strtolower($slugger->slug($data['name'])->toString()));
            $category->setDescription($data['description']);
            $this->em->persist($category);
            $categories[] = $category;
        }
        $this->em->flush();
        $io->success(count($categories) . ' categories created.');

        // --- Brands ---
        $brandsData = [
            ['name' => 'Apple', 'logo' => 'https://logo.clearbit.com/apple.com'],
            ['name' => 'Samsung', 'logo' => 'https://logo.clearbit.com/samsung.com'],
            ['name' => 'Sony', 'logo' => 'https://logo.clearbit.com/sony.com'],
            ['name' => 'Google', 'logo' => 'https://logo.clearbit.com/google.com'],
            ['name' => 'OnePlus', 'logo' => 'https://logo.clearbit.com/oneplus.com'],
            ['name' => 'Dell', 'logo' => 'https://logo.clearbit.com/dell.com'],
            ['name' => 'HP', 'logo' => 'https://logo.clearbit.com/hp.com'],
            ['name' => 'Lenovo', 'logo' => 'https://logo.clearbit.com/lenovo.com'],
            ['name' => 'Bose', 'logo' => 'https://logo.clearbit.com/bose.com'],
            ['name' => 'JBL', 'logo' => 'https://logo.clearbit.com/jbl.com'],
            ['name' => 'Microsoft', 'logo' => 'https://logo.clearbit.com/microsoft.com'],
            ['name' => 'Asus', 'logo' => 'https://logo.clearbit.com/asus.com'],
            ['name' => 'Xiaomi', 'logo' => 'https://logo.clearbit.com/xiaomi.com'],
            ['name' => 'Huawei', 'logo' => 'https://logo.clearbit.com/huawei.com'],
            ['name' => 'Canon', 'logo' => 'https://logo.clearbit.com/canon.com'],
            ['name' => 'Nikon', 'logo' => 'https://logo.clearbit.com/nikon.com'],
            ['name' => 'Nintendo', 'logo' => 'https://logo.clearbit.com/nintendo.com'],
            ['name' => 'LG', 'logo' => 'https://logo.clearbit.com/lg.com'],
            ['name' => 'Sennheiser', 'logo' => 'https://logo.clearbit.com/sennheiser.com'],
            ['name' => 'Anker', 'logo' => 'https://logo.clearbit.com/anker.com'],
        ];

        $io->section('Creating brands...');
        $brands = [];
        foreach ($brandsData as $data) {
            $brand = new Brand();
            $brand->setName($data['name']);
            $brand->setSlug(strtolower($slugger->slug($data['name'])->toString()));
            $brand->setLogoUrl($data['logo']);
            $this->em->persist($brand);
            $brands[] = $brand;
        }
        $this->em->flush();
        $io->success(count($brands) . ' brands created.');

        // --- Products ---
        $io->section('Creating products...');

        $products = $this->getProductTemplates();
        $count = 0;

        foreach ($products as $productData) {
            $product = new Product();
            $product->setName($productData['name']);
            $product->setSlug(strtolower($slugger->slug($productData['name'])->toString()) . '-' . (++$count));
            $product->setDescription($productData['description']);
            $product->setPrice($productData['price']);
            $product->setOriginalPrice($productData['original_price'] ?? null);
            $product->setRating($productData['rating']);
            $product->setReviewCount($productData['review_count']);
            $product->setImageUrl($productData['image_url'] ?? null);
            $product->setStockStatus($productData['stock_status'] ?? 'in_stock');
            $product->setSku('CIQ-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT));
            $product->setSpecifications($productData['specs'] ?? null);
            $product->setTags($productData['tags'] ?? []);

            // Assign category and brand
            $categoryIndex = $productData['category_index'];
            $brandIndex = $productData['brand_index'];
            $product->setCategory($categories[$categoryIndex]);
            $product->setBrand($brands[$brandIndex]);

            // Stagger creation dates over the last 180 days
            $daysAgo = rand(0, 180);
            $product->setCreatedAt(new \DateTime("-{$daysAgo} days"));
            $product->setUpdatedAt(new \DateTime("-" . rand(0, $daysAgo) . " days"));

            $this->em->persist($product);

            if ($count % 50 === 0) {
                $this->em->flush();
                $this->em->clear();
                // Re-fetch references after clear
                $categories = $this->em->getRepository(Category::class)->findAll();
                $brands = $this->em->getRepository(Brand::class)->findAll();
                $io->text("  ... {$count} products created");
            }
        }

        $this->em->flush();

        $io->newLine();
        $io->success("✅ Seeding complete! {$count} products created across " . count($categories) . ' categories and ' . count($brands) . ' brands.');

        return Command::SUCCESS;
    }

    /**
     * Returns ~500 product templates across all categories.
     */
    private function getProductTemplates(): array
    {
        $products = [];

        // ========================================
        // SMARTPHONES (category_index: 0)
        // ========================================
        $smartphones = [
            ['name' => 'iPhone 15 Pro Max', 'brand_index' => 0, 'price' => '1199.99', 'original_price' => '1299.99', 'rating' => '4.8', 'review_count' => 3421, 'description' => 'The most powerful iPhone ever. Features A17 Pro chip, titanium design, 48MP camera system with 5x optical zoom, and USB-C connectivity. The ultimate smartphone for professionals and creatives.', 'tags' => ['5g', 'titanium', 'pro', 'usb-c'], 'specs' => ['display' => '6.7" Super Retina XDR', 'chip' => 'A17 Pro', 'storage' => '256GB', 'battery' => '4441mAh']],
            ['name' => 'iPhone 15 Pro', 'brand_index' => 0, 'price' => '999.99', 'rating' => '4.7', 'review_count' => 2856, 'description' => 'Pro-level performance in a compact titanium design. A17 Pro chip, 48MP camera with 3x optical zoom, and Action Button. The perfect phone for those who want pro features in a smaller form.', 'tags' => ['5g', 'titanium', 'pro'], 'specs' => ['display' => '6.1" Super Retina XDR', 'chip' => 'A17 Pro', 'storage' => '128GB']],
            ['name' => 'iPhone 15', 'brand_index' => 0, 'price' => '799.99', 'rating' => '4.6', 'review_count' => 4512, 'description' => 'A new era of iPhone with Dynamic Island, 48MP camera, and USB-C. Beautiful colors and all-day battery life make this the most popular iPhone.', 'tags' => ['5g', 'usb-c'], 'specs' => ['display' => '6.1" Super Retina XDR', 'chip' => 'A16 Bionic', 'storage' => '128GB']],
            ['name' => 'iPhone 14', 'brand_index' => 0, 'price' => '599.99', 'original_price' => '699.99', 'rating' => '4.5', 'review_count' => 6789, 'description' => 'Great value with excellent camera and performance. Features crash detection and Emergency SOS via satellite.', 'tags' => ['5g', 'budget-friendly'], 'specs' => ['display' => '6.1" Super Retina XDR', 'chip' => 'A15 Bionic']],
            ['name' => 'Samsung Galaxy S24 Ultra', 'brand_index' => 1, 'price' => '1299.99', 'rating' => '4.7', 'review_count' => 2890, 'description' => 'The ultimate Galaxy experience with built-in S Pen, 200MP camera, titanium frame, and Galaxy AI features for photo editing, translation, and more.', 'tags' => ['5g', 'ai', 's-pen', 'titanium'], 'specs' => ['display' => '6.8" Dynamic AMOLED 2X', 'chip' => 'Snapdragon 8 Gen 3', 'storage' => '256GB']],
            ['name' => 'Samsung Galaxy S24+', 'brand_index' => 1, 'price' => '999.99', 'rating' => '4.5', 'review_count' => 1567, 'description' => 'Big screen, big performance. Galaxy AI powered with 50MP camera and all-day battery. Perfect for those who want a large display without the Ultra price.', 'tags' => ['5g', 'ai'], 'specs' => ['display' => '6.7" Dynamic AMOLED 2X', 'chip' => 'Snapdragon 8 Gen 3']],
            ['name' => 'Samsung Galaxy S24', 'brand_index' => 1, 'price' => '799.99', 'rating' => '4.4', 'review_count' => 2345, 'description' => 'Compact Galaxy flagship with Galaxy AI. Powerful performance and great camera in a pocket-friendly size.', 'tags' => ['5g', 'ai', 'compact'], 'specs' => ['display' => '6.2" Dynamic AMOLED 2X']],
            ['name' => 'Samsung Galaxy A54', 'brand_index' => 1, 'price' => '449.99', 'original_price' => '499.99', 'rating' => '4.3', 'review_count' => 5678, 'description' => 'Best mid-range smartphone with premium features. Water resistant, long battery life, and excellent camera for the price.', 'tags' => ['5g', 'mid-range', 'water-resistant'], 'specs' => ['display' => '6.4" Super AMOLED']],
            ['name' => 'Google Pixel 8 Pro', 'brand_index' => 3, 'price' => '999.99', 'rating' => '4.6', 'review_count' => 1890, 'description' => 'The best of Google AI in a phone. Tensor G3 chip, pro-level camera with Magic Eraser, Best Take, and 7 years of OS updates.', 'tags' => ['5g', 'ai', 'pure-android'], 'specs' => ['display' => '6.7" LTPO OLED', 'chip' => 'Tensor G3', 'camera' => '50MP + 48MP + 48MP']],
            ['name' => 'Google Pixel 8', 'brand_index' => 3, 'price' => '699.99', 'rating' => '4.5', 'review_count' => 2456, 'description' => 'Googles best phone at a great price. Amazing camera, clean Android experience, and AI features that actually help in daily life.', 'tags' => ['5g', 'ai', 'pure-android'], 'specs' => ['display' => '6.2" OLED', 'chip' => 'Tensor G3']],
            ['name' => 'Google Pixel 7a', 'brand_index' => 3, 'price' => '449.99', 'original_price' => '499.99', 'rating' => '4.4', 'review_count' => 3234, 'description' => 'Flagship features at a mid-range price. 64MP camera, Tensor G2 chip, and the pure Google experience.', 'tags' => ['5g', 'budget-friendly'], 'specs' => ['display' => '6.1" OLED']],
            ['name' => 'OnePlus 12', 'brand_index' => 4, 'price' => '799.99', 'rating' => '4.5', 'review_count' => 1234, 'description' => 'Flagship killer returns. Snapdragon 8 Gen 3, 100W fast charging, Hasselblad camera system. Never Settle.', 'tags' => ['5g', 'fast-charging'], 'specs' => ['display' => '6.82" LTPO AMOLED', 'chip' => 'Snapdragon 8 Gen 3', 'charging' => '100W SUPERVOOC']],
            ['name' => 'OnePlus Nord CE 3', 'brand_index' => 4, 'price' => '299.99', 'rating' => '4.2', 'review_count' => 1890, 'description' => 'Premium mid-range with 108MP camera and 67W fast charging. Great value Android phone.', 'tags' => ['5g', 'budget-friendly', 'fast-charging'], 'specs' => ['display' => '6.7" AMOLED']],
            ['name' => 'Xiaomi 14 Ultra', 'brand_index' => 12, 'price' => '899.99', 'rating' => '4.4', 'review_count' => 876, 'description' => 'Leica camera system with variable aperture. Photography enthusiasts dream phone with Snapdragon 8 Gen 3.', 'tags' => ['5g', 'leica', 'photography'], 'specs' => ['display' => '6.73" LTPO AMOLED', 'camera' => '50MP Leica Summilux']],
            ['name' => 'Xiaomi Redmi Note 13 Pro', 'brand_index' => 12, 'price' => '249.99', 'rating' => '4.1', 'review_count' => 4567, 'description' => 'Best budget phone with 200MP camera. Incredible value with AMOLED display and fast charging.', 'tags' => ['budget', '200mp'], 'specs' => ['display' => '6.67" AMOLED', 'camera' => '200MP']],
        ];

        // ========================================
        // LAPTOPS (category_index: 1)
        // ========================================
        $laptops = [
            ['name' => 'MacBook Pro 16" M3 Max', 'brand_index' => 0, 'price' => '3499.99', 'rating' => '4.9', 'review_count' => 1234, 'description' => 'The most powerful MacBook ever. M3 Max chip with up to 128GB unified memory. Space Black finish. The ultimate laptop for professionals working with video, 3D, and code.', 'tags' => ['m3-max', 'pro', 'space-black'], 'specs' => ['display' => '16.2" Liquid Retina XDR', 'chip' => 'M3 Max', 'ram' => '36GB', 'storage' => '1TB SSD']],
            ['name' => 'MacBook Pro 14" M3 Pro', 'brand_index' => 0, 'price' => '1999.99', 'rating' => '4.8', 'review_count' => 2345, 'description' => 'Pro performance in a portable size. M3 Pro chip, stunning Liquid Retina XDR display, and all-day battery life.', 'tags' => ['m3-pro', 'pro', 'portable'], 'specs' => ['display' => '14.2" Liquid Retina XDR', 'chip' => 'M3 Pro', 'ram' => '18GB']],
            ['name' => 'MacBook Air 15" M3', 'brand_index' => 0, 'price' => '1299.99', 'rating' => '4.7', 'review_count' => 3456, 'description' => 'Impossibly thin with a 15-inch display. M3 chip delivers fast performance with up to 18 hours of battery life. Fanless design.', 'tags' => ['m3', 'thin', 'fanless'], 'specs' => ['display' => '15.3" Liquid Retina', 'chip' => 'M3', 'ram' => '8GB']],
            ['name' => 'MacBook Air 13" M3', 'brand_index' => 0, 'price' => '1099.99', 'rating' => '4.7', 'review_count' => 5678, 'description' => 'The worlds best laptop gets even better. M3 chip, stunning display, and the lightest laptop Apple has ever made.', 'tags' => ['m3', 'ultralight', 'fanless'], 'specs' => ['display' => '13.6" Liquid Retina', 'chip' => 'M3']],
            ['name' => 'Dell XPS 15', 'brand_index' => 5, 'price' => '1799.99', 'original_price' => '1999.99', 'rating' => '4.5', 'review_count' => 1567, 'description' => 'Stunning InfinityEdge display with Intel Core Ultra. Premium build quality with carbon fiber palm rest. Perfect for creators.', 'tags' => ['intel', 'creator', 'infinityedge'], 'specs' => ['display' => '15.6" OLED 3.5K', 'cpu' => 'Intel Core Ultra 7', 'ram' => '16GB']],
            ['name' => 'Dell XPS 13', 'brand_index' => 5, 'price' => '1199.99', 'rating' => '4.4', 'review_count' => 2890, 'description' => 'Ultra-portable powerhouse with InfinityEdge display. Thinnest XPS ever with remarkable performance.', 'tags' => ['intel', 'ultrabook'], 'specs' => ['display' => '13.4" FHD+', 'cpu' => 'Intel Core Ultra 5']],
            ['name' => 'Dell Inspiron 15', 'brand_index' => 5, 'price' => '599.99', 'original_price' => '699.99', 'rating' => '4.2', 'review_count' => 4567, 'description' => 'Reliable everyday laptop with great value. Perfect for students and home office use.', 'tags' => ['budget', 'everyday'], 'specs' => ['display' => '15.6" FHD', 'cpu' => 'Intel Core i5']],
            ['name' => 'HP Spectre x360 16', 'brand_index' => 6, 'price' => '1599.99', 'rating' => '4.6', 'review_count' => 987, 'description' => 'Premium 2-in-1 convertible with OLED display. Gem-cut design with Intel Core Ultra processor and stylus support.', 'tags' => ['2-in-1', 'oled', 'premium'], 'specs' => ['display' => '16" OLED 3K', 'cpu' => 'Intel Core Ultra 7']],
            ['name' => 'HP Pavilion 15', 'brand_index' => 6, 'price' => '549.99', 'rating' => '4.1', 'review_count' => 3456, 'description' => 'Affordable performance for everyday tasks. Good display, solid build, and decent battery life.', 'tags' => ['budget', 'everyday'], 'specs' => ['display' => '15.6" FHD IPS']],
            ['name' => 'Lenovo ThinkPad X1 Carbon Gen 11', 'brand_index' => 7, 'price' => '1649.99', 'original_price' => '1849.99', 'rating' => '4.7', 'review_count' => 1234, 'description' => 'The legendary business laptop, now lighter and more powerful. MIL-STD tested, TrackPoint, and legendary keyboard.', 'tags' => ['business', 'thinkpad', 'lightweight'], 'specs' => ['display' => '14" WUXGA IPS', 'cpu' => 'Intel Core i7-1365U']],
            ['name' => 'Lenovo IdeaPad Slim 5', 'brand_index' => 7, 'price' => '699.99', 'rating' => '4.3', 'review_count' => 2345, 'description' => 'Slim, stylish, and powerful. AMD Ryzen processor with great battery life for students and professionals.', 'tags' => ['amd', 'slim', 'student'], 'specs' => ['display' => '14" WUXGA IPS', 'cpu' => 'AMD Ryzen 7']],
            ['name' => 'Asus ROG Zephyrus G16', 'brand_index' => 11, 'price' => '1999.99', 'rating' => '4.6', 'review_count' => 876, 'description' => 'Ultra-slim gaming laptop with RTX 4070. OLED display with 240Hz refresh rate. Gaming power in a creators body.', 'tags' => ['gaming', 'rtx-4070', 'oled'], 'specs' => ['display' => '16" OLED 240Hz', 'gpu' => 'RTX 4070', 'cpu' => 'Intel Core i9']],
            ['name' => 'Asus ZenBook 14 OLED', 'brand_index' => 11, 'price' => '899.99', 'rating' => '4.4', 'review_count' => 1567, 'description' => 'Stunning OLED display in an ultralight package. Great for content consumption and productivity.', 'tags' => ['oled', 'ultralight'], 'specs' => ['display' => '14" OLED 2.8K']],
            ['name' => 'Microsoft Surface Laptop 5', 'brand_index' => 10, 'price' => '999.99', 'original_price' => '1299.99', 'rating' => '4.3', 'review_count' => 1890, 'description' => 'Elegant design meets powerful performance. Touchscreen display with premium Alcantara keyboard deck.', 'tags' => ['surface', 'touchscreen'], 'specs' => ['display' => '13.5" PixelSense', 'cpu' => 'Intel Core i5']],
            ['name' => 'Microsoft Surface Pro 9', 'brand_index' => 10, 'price' => '1099.99', 'rating' => '4.4', 'review_count' => 1234, 'description' => 'The tablet that can replace your laptop. Detachable keyboard, Surface Pen support, and all-day battery.', 'tags' => ['2-in-1', 'surface', 'tablet'], 'specs' => ['display' => '13" PixelSense Flow']],
        ];

        // ========================================
        // HEADPHONES (category_index: 2)
        // ========================================
        $headphones = [
            ['name' => 'AirPods Pro 2nd Gen', 'brand_index' => 0, 'price' => '249.99', 'rating' => '4.8', 'review_count' => 8765, 'description' => 'Adaptive Audio, Active Noise Cancellation, and Transparency mode. USB-C with up to 6 hours of listening time. The best earbuds for iPhone users.', 'tags' => ['anc', 'wireless', 'usb-c'], 'specs' => ['type' => 'In-ear', 'anc' => 'Yes', 'battery' => '6 hours']],
            ['name' => 'AirPods Max', 'brand_index' => 0, 'price' => '549.99', 'rating' => '4.6', 'review_count' => 2345, 'description' => 'Over-ear headphones with computational audio, ANC, and spatial audio. Premium aluminum design with Digital Crown.', 'tags' => ['anc', 'over-ear', 'premium', 'spatial-audio'], 'specs' => ['type' => 'Over-ear', 'anc' => 'Yes', 'battery' => '20 hours']],
            ['name' => 'AirPods 3rd Gen', 'brand_index' => 0, 'price' => '169.99', 'rating' => '4.4', 'review_count' => 5678, 'description' => 'Spatial audio with dynamic head tracking. Sweat and water resistant with MagSafe charging case.', 'tags' => ['wireless', 'spatial-audio'], 'specs' => ['type' => 'In-ear', 'battery' => '6 hours']],
            ['name' => 'Sony WH-1000XM5', 'brand_index' => 2, 'price' => '349.99', 'original_price' => '399.99', 'rating' => '4.8', 'review_count' => 6789, 'description' => 'Industry-leading noise cancellation with Auto NC Optimizer. 30-hour battery, multipoint connection, and crystal clear calls. The gold standard of ANC headphones.', 'tags' => ['anc', 'over-ear', 'best-in-class'], 'specs' => ['type' => 'Over-ear', 'anc' => 'Yes', 'battery' => '30 hours']],
            ['name' => 'Sony WF-1000XM5', 'brand_index' => 2, 'price' => '299.99', 'rating' => '4.7', 'review_count' => 3456, 'description' => 'The worlds smallest and lightest noise cancelling earbuds. Premium sound quality with LDAC and DSEE Extreme.', 'tags' => ['anc', 'wireless', 'compact'], 'specs' => ['type' => 'In-ear', 'anc' => 'Yes', 'battery' => '8 hours']],
            ['name' => 'Sony WH-1000XM4', 'brand_index' => 2, 'price' => '248.00', 'original_price' => '349.99', 'rating' => '4.7', 'review_count' => 12345, 'description' => 'Previous gen flagship still going strong. Amazing ANC, 30-hour battery, and LDAC support at a great price.', 'tags' => ['anc', 'over-ear', 'value'], 'specs' => ['type' => 'Over-ear', 'anc' => 'Yes', 'battery' => '30 hours']],
            ['name' => 'Bose QuietComfort Ultra', 'brand_index' => 8, 'price' => '429.99', 'rating' => '4.7', 'review_count' => 2345, 'description' => 'Immersive audio with Bose Immersive Audio technology. World-class noise cancellation and luxurious comfort.', 'tags' => ['anc', 'over-ear', 'immersive'], 'specs' => ['type' => 'Over-ear', 'anc' => 'Yes', 'battery' => '24 hours']],
            ['name' => 'Bose QuietComfort Earbuds II', 'brand_index' => 8, 'price' => '279.99', 'original_price' => '299.99', 'rating' => '4.6', 'review_count' => 4567, 'description' => 'CustomTune sound calibration for personalized ANC. Compact design with secure fit and amazing call quality.', 'tags' => ['anc', 'wireless', 'custom-tune'], 'specs' => ['type' => 'In-ear', 'anc' => 'Yes', 'battery' => '6 hours']],
            ['name' => 'Bose 700 Headphones', 'brand_index' => 8, 'price' => '379.99', 'original_price' => '399.99', 'rating' => '4.5', 'review_count' => 5678, 'description' => 'Sleek design with 11 levels of noise cancellation. Touch controls and superior microphone system for calls.', 'tags' => ['anc', 'over-ear', 'design'], 'specs' => ['type' => 'Over-ear', 'anc' => 'Yes', 'battery' => '20 hours']],
            ['name' => 'Samsung Galaxy Buds3 Pro', 'brand_index' => 1, 'price' => '249.99', 'rating' => '4.4', 'review_count' => 1567, 'description' => 'Premium Galaxy earbuds with ANC and 360 Audio. Blade design with enhanced Galaxy AI features.', 'tags' => ['anc', 'wireless', 'galaxy'], 'specs' => ['type' => 'In-ear', 'anc' => 'Yes', 'battery' => '7 hours']],
            ['name' => 'JBL Tour One M2', 'brand_index' => 9, 'price' => '299.99', 'rating' => '4.4', 'review_count' => 1234, 'description' => 'True Adaptive Noise Cancelling with Personi-Fi 3.0 for personalized sound. 50-hour battery life.', 'tags' => ['anc', 'over-ear', 'long-battery'], 'specs' => ['type' => 'Over-ear', 'anc' => 'Yes', 'battery' => '50 hours']],
            ['name' => 'JBL Tune 760NC', 'brand_index' => 9, 'price' => '99.99', 'original_price' => '129.99', 'rating' => '4.2', 'review_count' => 6789, 'description' => 'Affordable ANC headphones with JBL Pure Bass. Great sound quality and noise cancellation at a budget price.', 'tags' => ['anc', 'over-ear', 'budget'], 'specs' => ['type' => 'Over-ear', 'anc' => 'Yes', 'battery' => '35 hours']],
            ['name' => 'Sennheiser Momentum 4', 'brand_index' => 18, 'price' => '349.99', 'rating' => '4.6', 'review_count' => 1890, 'description' => 'Audiophile-grade wireless headphones with adaptive ANC. 60-hour battery life and premium materials.', 'tags' => ['anc', 'audiophile', 'premium'], 'specs' => ['type' => 'Over-ear', 'anc' => 'Yes', 'battery' => '60 hours']],
            ['name' => 'Sennheiser HD 660S2', 'brand_index' => 18, 'price' => '499.99', 'rating' => '4.8', 'review_count' => 876, 'description' => 'Open-back reference headphones for audiophiles. Natural, detailed sound reproduction for critical listening.', 'tags' => ['open-back', 'wired', 'audiophile', 'reference'], 'specs' => ['type' => 'Over-ear', 'open-back' => 'Yes', 'impedance' => '300 Ohm']],
        ];

        // ========================================
        // TABLETS (category_index: 3)
        // ========================================
        $tablets = [
            ['name' => 'iPad Pro 12.9" M2', 'brand_index' => 0, 'price' => '1099.99', 'rating' => '4.8', 'review_count' => 3456, 'description' => 'The ultimate iPad experience with M2 chip, Liquid Retina XDR display, and Apple Pencil hover. The most powerful tablet for creatives.', 'tags' => ['m2', 'pro', 'pencil'], 'specs' => ['display' => '12.9" Liquid Retina XDR', 'chip' => 'M2']],
            ['name' => 'iPad Air M2', 'brand_index' => 0, 'price' => '599.99', 'rating' => '4.7', 'review_count' => 4567, 'description' => 'Powerful and versatile with M2 chip. Perfect balance of performance and portability.', 'tags' => ['m2', 'versatile'], 'specs' => ['display' => '11" Liquid Retina', 'chip' => 'M2']],
            ['name' => 'iPad 10th Gen', 'brand_index' => 0, 'price' => '449.99', 'rating' => '4.5', 'review_count' => 5678, 'description' => 'Colorful, capable, and fun. Redesigned with USB-C, 5G option, and landscape camera. Great for everyone.', 'tags' => ['usb-c', 'colorful'], 'specs' => ['display' => '10.9" Liquid Retina', 'chip' => 'A14 Bionic']],
            ['name' => 'iPad Mini 6', 'brand_index' => 0, 'price' => '499.99', 'rating' => '4.6', 'review_count' => 2345, 'description' => 'Mighty and portable. A15 Bionic chip in an 8.3-inch form factor. Perfect for reading and on-the-go productivity.', 'tags' => ['portable', 'compact'], 'specs' => ['display' => '8.3" Liquid Retina', 'chip' => 'A15 Bionic']],
            ['name' => 'Samsung Galaxy Tab S9 Ultra', 'brand_index' => 1, 'price' => '1199.99', 'rating' => '4.6', 'review_count' => 1234, 'description' => 'Massive 14.6" Dynamic AMOLED display with S Pen included. Snapdragon 8 Gen 2 for Galaxy. The biggest Android tablet.', 'tags' => ['s-pen', 'ultra', 'amoled'], 'specs' => ['display' => '14.6" Dynamic AMOLED 2X', 'chip' => 'Snapdragon 8 Gen 2']],
            ['name' => 'Samsung Galaxy Tab S9', 'brand_index' => 1, 'price' => '799.99', 'rating' => '4.5', 'review_count' => 1890, 'description' => 'Premium Android tablet with S Pen and Dynamic AMOLED display. DeX mode for desktop-like experience.', 'tags' => ['s-pen', 'dex'], 'specs' => ['display' => '11" Dynamic AMOLED 2X']],
            ['name' => 'Samsung Galaxy Tab A9+', 'brand_index' => 1, 'price' => '269.99', 'rating' => '4.2', 'review_count' => 3456, 'description' => 'Affordable tablet for entertainment and basic productivity. Great display and long battery life.', 'tags' => ['budget', 'entertainment'], 'specs' => ['display' => '11" TFT LCD']],
            ['name' => 'Lenovo Tab P12 Pro', 'brand_index' => 7, 'price' => '679.99', 'original_price' => '749.99', 'rating' => '4.3', 'review_count' => 987, 'description' => 'Premium Android tablet with OLED display and Precision Pen 3. Great for entertainment and productivity.', 'tags' => ['oled', 'stylus'], 'specs' => ['display' => '12.7" OLED 2K']],
            ['name' => 'Microsoft Surface Pro 9', 'brand_index' => 10, 'price' => '1099.99', 'rating' => '4.4', 'review_count' => 1567, 'description' => 'The versatile 2-in-1 with Windows 11. Detachable Type Cover and Surface Slim Pen 2. Full PC in tablet form.', 'tags' => ['2-in-1', 'windows', 'surface'], 'specs' => ['display' => '13" PixelSense Flow', 'cpu' => 'Intel Core i5']],
            ['name' => 'Xiaomi Pad 6', 'brand_index' => 12, 'price' => '299.99', 'rating' => '4.3', 'review_count' => 2345, 'description' => 'Great value tablet with 144Hz display and Snapdragon 870. Quad speakers for immersive entertainment.', 'tags' => ['144hz', 'value', 'quad-speakers'], 'specs' => ['display' => '11" IPS LCD 144Hz']],
        ];

        // ========================================
        // SMARTWATCHES (category_index: 4)
        // ========================================
        $smartwatches = [
            ['name' => 'Apple Watch Ultra 2', 'brand_index' => 0, 'price' => '799.99', 'rating' => '4.8', 'review_count' => 2345, 'description' => 'The most rugged Apple Watch with S9 chip, precision dual-frequency GPS, and 36-hour battery. Built for extreme adventures.', 'tags' => ['rugged', 'gps', 'diving'], 'specs' => ['display' => '49mm', 'chip' => 'S9', 'water_resistance' => '100m']],
            ['name' => 'Apple Watch Series 9', 'brand_index' => 0, 'price' => '399.99', 'rating' => '4.7', 'review_count' => 4567, 'description' => 'S9 chip with Double Tap gesture. Brighter display, on-device Siri, and comprehensive health tracking.', 'tags' => ['health', 'double-tap'], 'specs' => ['display' => '45mm', 'chip' => 'S9']],
            ['name' => 'Apple Watch SE 2', 'brand_index' => 0, 'price' => '249.99', 'rating' => '4.5', 'review_count' => 5678, 'description' => 'Essential Apple Watch features at an accessible price. Crash detection, fall detection, and fitness tracking.', 'tags' => ['affordable', 'fitness'], 'specs' => ['display' => '44mm', 'chip' => 'S8']],
            ['name' => 'Samsung Galaxy Watch 6 Classic', 'brand_index' => 1, 'price' => '399.99', 'rating' => '4.5', 'review_count' => 1890, 'description' => 'Classic rotating bezel returns with advanced health monitoring. Body composition, sleep coaching, and Wear OS.', 'tags' => ['rotating-bezel', 'health', 'wear-os'], 'specs' => ['display' => '47mm Super AMOLED']],
            ['name' => 'Samsung Galaxy Watch 6', 'brand_index' => 1, 'price' => '299.99', 'rating' => '4.4', 'review_count' => 2345, 'description' => 'Sleek design with comprehensive health tracking. Larger display in a slimmer body.', 'tags' => ['slim', 'health', 'wear-os'], 'specs' => ['display' => '44mm Super AMOLED']],
            ['name' => 'Google Pixel Watch 2', 'brand_index' => 3, 'price' => '349.99', 'rating' => '4.3', 'review_count' => 1234, 'description' => 'Beautifully designed with Fitbit health tracking. Wear OS by Google with seamless Pixel integration.', 'tags' => ['fitbit', 'pixel', 'wear-os'], 'specs' => ['display' => '41mm AMOLED']],
            ['name' => 'Xiaomi Watch 2 Pro', 'brand_index' => 12, 'price' => '229.99', 'rating' => '4.1', 'review_count' => 1567, 'description' => 'Premium smartwatch with Wear OS and cellular support. GPS, heart rate, SpO2 tracking at an affordable price.', 'tags' => ['wear-os', 'cellular', 'affordable'], 'specs' => ['display' => '46mm AMOLED']],
            ['name' => 'Huawei Watch GT 4', 'brand_index' => 13, 'price' => '249.99', 'rating' => '4.3', 'review_count' => 1890, 'description' => 'Elegant design with 14-day battery life. Advanced health monitoring with TruSeen 5.5+ heart rate technology.', 'tags' => ['long-battery', 'elegant'], 'specs' => ['display' => '46mm AMOLED', 'battery' => '14 days']],
        ];

        // ========================================
        // CAMERAS (category_index: 5)
        // ========================================
        $cameras = [
            ['name' => 'Sony Alpha A7 IV', 'brand_index' => 2, 'price' => '2498.00', 'rating' => '4.8', 'review_count' => 1890, 'description' => 'Full-frame hybrid camera with 33MP sensor, real-time Eye AF, and 4K 60fps video. The new standard for mirrorless.', 'tags' => ['full-frame', 'mirrorless', '4k'], 'specs' => ['sensor' => '33MP Full-Frame', 'video' => '4K 60fps', 'af' => 'Real-time Eye AF']],
            ['name' => 'Sony Alpha A7C II', 'brand_index' => 2, 'price' => '2198.00', 'rating' => '4.6', 'review_count' => 876, 'description' => 'Compact full-frame mirrorless with AI AF. Perfect for travel and street photography.', 'tags' => ['compact', 'full-frame', 'travel'], 'specs' => ['sensor' => '33MP Full-Frame', 'weight' => '514g']],
            ['name' => 'Sony ZV-E10 II', 'brand_index' => 2, 'price' => '999.99', 'rating' => '4.5', 'review_count' => 2345, 'description' => 'The ultimate vlogging camera with 26MP APS-C sensor, flip screen, and cinematic vlog mode.', 'tags' => ['vlogging', 'aps-c', 'content-creator'], 'specs' => ['sensor' => '26MP APS-C', 'screen' => 'Flip']],
            ['name' => 'Canon EOS R6 Mark II', 'brand_index' => 14, 'price' => '2499.00', 'rating' => '4.7', 'review_count' => 1567, 'description' => 'Versatile full-frame mirrorless with 24.2MP sensor, advanced subject detection AF, and 40fps burst shooting.', 'tags' => ['full-frame', 'mirrorless', 'fast-af'], 'specs' => ['sensor' => '24.2MP Full-Frame', 'burst' => '40fps', 'video' => '4K 60fps']],
            ['name' => 'Canon EOS R50', 'brand_index' => 14, 'price' => '679.99', 'rating' => '4.4', 'review_count' => 2345, 'description' => 'Entry-level mirrorless with advanced AF. Easy to use with automatic scene detection and creative filters.', 'tags' => ['entry-level', 'mirrorless', 'beginner'], 'specs' => ['sensor' => '24.2MP APS-C', 'weight' => '329g']],
            ['name' => 'Canon PowerShot V10', 'brand_index' => 14, 'price' => '429.99', 'rating' => '4.2', 'review_count' => 1234, 'description' => 'Pocket-sized vlog camera with built-in stand and wide-angle lens. 4K video with IS for steady footage.', 'tags' => ['vlogging', 'pocket', '4k'], 'specs' => ['sensor' => '1" CMOS', 'video' => '4K 30fps']],
            ['name' => 'Nikon Z8', 'brand_index' => 15, 'price' => '3996.95', 'rating' => '4.8', 'review_count' => 987, 'description' => 'Professional mirrorless with 45.7MP stacked sensor, 8K video, and dual card slots. Built for professionals.', 'tags' => ['professional', '8k', 'stacked-sensor'], 'specs' => ['sensor' => '45.7MP Full-Frame Stacked', 'video' => '8K 30fps']],
            ['name' => 'Nikon Z fc', 'brand_index' => 15, 'price' => '956.95', 'rating' => '4.5', 'review_count' => 1567, 'description' => 'Retro-styled mirrorless with modern internals. Beautiful heritage design with 20.9MP DX sensor.', 'tags' => ['retro', 'aps-c', 'stylish'], 'specs' => ['sensor' => '20.9MP DX', 'design' => 'Heritage']],
            ['name' => 'Sony Alpha A6700', 'brand_index' => 2, 'price' => '1398.00', 'rating' => '4.6', 'review_count' => 1234, 'description' => 'Flagship APS-C camera with AI-based AF, 26MP sensor, and 4K 120fps. Perfect hybrid camera.', 'tags' => ['aps-c', 'ai-af', '4k-120fps'], 'specs' => ['sensor' => '26MP APS-C', 'video' => '4K 120fps']],
            ['name' => 'Canon EOS R100', 'brand_index' => 14, 'price' => '479.99', 'original_price' => '599.99', 'rating' => '4.1', 'review_count' => 1890, 'description' => 'Most affordable Canon mirrorless. Simple to use, lightweight, and great image quality for beginners.', 'tags' => ['beginner', 'lightweight', 'affordable'], 'specs' => ['sensor' => '24.1MP APS-C']],
        ];

        // ========================================
        // SPEAKERS (category_index: 6)
        // ========================================
        $speakers = [
            ['name' => 'Apple HomePod 2nd Gen', 'brand_index' => 0, 'price' => '299.99', 'rating' => '4.5', 'review_count' => 2345, 'description' => 'Room-filling sound with spatial audio. Siri integration, smart home hub, and sound recognition features.', 'tags' => ['smart-speaker', 'siri', 'spatial-audio'], 'specs' => ['type' => 'Smart Speaker', 'assistant' => 'Siri']],
            ['name' => 'Apple HomePod Mini', 'brand_index' => 0, 'price' => '99.99', 'rating' => '4.4', 'review_count' => 6789, 'description' => 'Big sound in a compact design. Siri, Intercom, and smart home control in a tiny sphere.', 'tags' => ['smart-speaker', 'compact', 'siri'], 'specs' => ['type' => 'Smart Speaker Mini']],
            ['name' => 'Bose SoundLink Flex', 'brand_index' => 8, 'price' => '149.99', 'rating' => '4.6', 'review_count' => 4567, 'description' => 'Portable Bluetooth speaker with PositionIQ technology. IP67 waterproof, dustproof, and floats in water.', 'tags' => ['portable', 'waterproof', 'bluetooth'], 'specs' => ['type' => 'Portable', 'waterproof' => 'IP67', 'battery' => '12 hours']],
            ['name' => 'Bose SoundLink Revolve+ II', 'brand_index' => 8, 'price' => '329.99', 'rating' => '4.5', 'review_count' => 2345, 'description' => '360-degree sound with deep bass. Portable with carry handle and up to 17 hours of battery life.', 'tags' => ['360-sound', 'portable', 'bluetooth'], 'specs' => ['type' => 'Portable 360', 'battery' => '17 hours']],
            ['name' => 'JBL Charge 5', 'brand_index' => 9, 'price' => '179.99', 'rating' => '4.7', 'review_count' => 8765, 'description' => 'Powerful portable speaker with built-in power bank. IP67 waterproof with PartyBoost for stereo pairing.', 'tags' => ['portable', 'waterproof', 'power-bank'], 'specs' => ['type' => 'Portable', 'waterproof' => 'IP67', 'battery' => '20 hours']],
            ['name' => 'JBL Flip 6', 'brand_index' => 9, 'price' => '129.99', 'rating' => '4.6', 'review_count' => 9876, 'description' => 'Bold sound for every adventure. Compact, portable, and waterproof with 12 hours of playtime.', 'tags' => ['portable', 'waterproof', 'compact'], 'specs' => ['type' => 'Portable', 'waterproof' => 'IP67', 'battery' => '12 hours']],
            ['name' => 'JBL Xtreme 3', 'brand_index' => 9, 'price' => '349.99', 'rating' => '4.5', 'review_count' => 3456, 'description' => 'Massive portable speaker with incredible bass. IP67 waterproof with 15 hours of battery and built-in bottle opener.', 'tags' => ['portable', 'powerful', 'waterproof'], 'specs' => ['type' => 'Portable Large', 'battery' => '15 hours']],
            ['name' => 'Sony SRS-XB100', 'brand_index' => 2, 'price' => '59.99', 'rating' => '4.3', 'review_count' => 5678, 'description' => 'Ultra-portable speaker with Extra Bass. IP67 rated, hands-free calling, and 16 hours of battery.', 'tags' => ['ultra-portable', 'extra-bass', 'budget'], 'specs' => ['type' => 'Ultra Portable', 'battery' => '16 hours']],
            ['name' => 'Sonos One SL', 'brand_index' => 8, 'price' => '199.99', 'rating' => '4.5', 'review_count' => 3456, 'description' => 'Premium smart speaker without microphone. Rich, room-filling sound with Trueplay tuning.', 'tags' => ['smart-speaker', 'multi-room'], 'specs' => ['type' => 'Smart Speaker', 'multi_room' => 'Yes']],
            ['name' => 'Samsung Galaxy Home Mini', 'brand_index' => 1, 'price' => '49.99', 'rating' => '3.9', 'review_count' => 1234, 'description' => 'Compact smart speaker with Bixby. AKG tuned sound and SmartThings hub functionality.', 'tags' => ['smart-speaker', 'bixby', 'compact'], 'specs' => ['type' => 'Smart Speaker Mini']],
        ];

        // ========================================
        // GAMING (category_index: 7)
        // ========================================
        $gaming = [
            ['name' => 'PlayStation 5', 'brand_index' => 2, 'price' => '499.99', 'rating' => '4.8', 'review_count' => 12345, 'description' => 'Next-gen gaming with ultra-high speed SSD, ray tracing, 4K gaming, and haptic feedback. The future of gaming is here.', 'tags' => ['console', '4k', 'ray-tracing'], 'specs' => ['storage' => '825GB SSD', 'resolution' => '4K', 'fps' => '120fps']],
            ['name' => 'PlayStation 5 Digital', 'brand_index' => 2, 'price' => '399.99', 'rating' => '4.7', 'review_count' => 6789, 'description' => 'All-digital PS5 with no disc drive. Same incredible performance at a lower price.', 'tags' => ['console', 'digital', '4k'], 'specs' => ['storage' => '825GB SSD', 'resolution' => '4K']],
            ['name' => 'Xbox Series X', 'brand_index' => 10, 'price' => '499.99', 'rating' => '4.7', 'review_count' => 8765, 'description' => 'Most powerful Xbox ever with 12 teraflops of processing power. Quick Resume, Smart Delivery, and Game Pass.', 'tags' => ['console', '4k', 'game-pass'], 'specs' => ['storage' => '1TB SSD', 'resolution' => '4K', 'tflops' => '12']],
            ['name' => 'Xbox Series S', 'brand_index' => 10, 'price' => '299.99', 'rating' => '4.4', 'review_count' => 5678, 'description' => 'Smallest Xbox ever for next-gen gaming. All-digital with 512GB SSD and up to 120fps gaming.', 'tags' => ['console', 'digital', 'compact'], 'specs' => ['storage' => '512GB SSD', 'resolution' => '1440p']],
            ['name' => 'Nintendo Switch OLED', 'brand_index' => 16, 'price' => '349.99', 'rating' => '4.8', 'review_count' => 9876, 'description' => 'Vibrant 7-inch OLED screen with wide adjustable stand. Play at home or on the go with Nintendos iconic games.', 'tags' => ['handheld', 'hybrid', 'oled'], 'specs' => ['display' => '7" OLED', 'storage' => '64GB']],
            ['name' => 'Nintendo Switch Lite', 'brand_index' => 16, 'price' => '199.99', 'rating' => '4.5', 'review_count' => 7654, 'description' => 'Dedicated handheld gaming with all your favorite Nintendo games. Lightweight and portable.', 'tags' => ['handheld', 'portable', 'budget'], 'specs' => ['display' => '5.5" LCD', 'storage' => '32GB']],
            ['name' => 'Sony DualSense Controller', 'brand_index' => 2, 'price' => '69.99', 'rating' => '4.7', 'review_count' => 6789, 'description' => 'Revolutionary controller with haptic feedback and adaptive triggers. Feel the game like never before.', 'tags' => ['controller', 'haptic', 'ps5'], 'specs' => ['type' => 'Wireless Controller', 'battery' => '12 hours']],
            ['name' => 'Xbox Elite Series 2 Controller', 'brand_index' => 10, 'price' => '179.99', 'rating' => '4.5', 'review_count' => 3456, 'description' => 'Pro-grade controller with adjustable-tension thumbsticks, shorter hair trigger locks, and wrap-around rubberized grip.', 'tags' => ['controller', 'pro', 'xbox'], 'specs' => ['type' => 'Wireless Controller', 'battery' => '40 hours']],
            ['name' => 'Asus ROG Ally', 'brand_index' => 11, 'price' => '699.99', 'rating' => '4.3', 'review_count' => 1234, 'description' => 'Handheld gaming PC with AMD Z1 Extreme. Full Windows 11 gaming with a 7-inch 120Hz display.', 'tags' => ['handheld-pc', '120hz', 'windows'], 'specs' => ['display' => '7" FHD 120Hz', 'chip' => 'AMD Z1 Extreme']],
            ['name' => 'SteelSeries Arctis Nova Pro', 'brand_index' => 2, 'price' => '349.99', 'rating' => '4.6', 'review_count' => 1890, 'description' => 'Premium gaming headset with hot-swap battery, ANC, and Hi-Res audio. Multi-system connect via GameDAC Gen 2.', 'tags' => ['gaming-headset', 'anc', 'hi-res'], 'specs' => ['type' => 'Gaming Headset', 'anc' => 'Yes']],
        ];

        // ========================================
        // ACCESSORIES (category_index: 8)
        // ========================================
        $accessories = [
            ['name' => 'Apple MagSafe Charger', 'brand_index' => 0, 'price' => '39.99', 'rating' => '4.4', 'review_count' => 8765, 'description' => 'Wireless charger that perfectly aligns with iPhone. Up to 15W of charging power with MagSafe technology.', 'tags' => ['charger', 'wireless', 'magsafe'], 'specs' => ['type' => 'Wireless Charger', 'power' => '15W']],
            ['name' => 'Apple AirTag 4-Pack', 'brand_index' => 0, 'price' => '99.99', 'rating' => '4.6', 'review_count' => 6789, 'description' => 'Track your everyday items with precision finding. Uses the entire Find My network to locate your things.', 'tags' => ['tracker', 'find-my', 'bluetooth'], 'specs' => ['type' => 'Item Tracker', 'battery' => 'CR2032']],
            ['name' => 'Samsung 45W USB-C Charger', 'brand_index' => 1, 'price' => '49.99', 'rating' => '4.5', 'review_count' => 3456, 'description' => 'Super fast charging with 45W USB-C PD. Compatible with Galaxy phones, tablets, and laptops.', 'tags' => ['charger', 'usb-c', 'fast-charging'], 'specs' => ['type' => 'Wall Charger', 'power' => '45W']],
            ['name' => 'Anker 737 Power Bank', 'brand_index' => 19, 'price' => '109.99', 'original_price' => '149.99', 'rating' => '4.7', 'review_count' => 4567, 'description' => '24,000mAh portable charger with 140W output. Charge laptops, phones, and tablets. GaNPrime technology.', 'tags' => ['power-bank', '140w', 'laptop-charging'], 'specs' => ['capacity' => '24000mAh', 'output' => '140W', 'ports' => '2 USB-C, 1 USB-A']],
            ['name' => 'Anker Nano Charger 30W', 'brand_index' => 19, 'price' => '19.99', 'rating' => '4.6', 'review_count' => 12345, 'description' => 'Ultra-compact 30W USB-C charger. GaN technology makes it smaller than standard chargers while being more powerful.', 'tags' => ['charger', 'gan', 'compact'], 'specs' => ['type' => 'Wall Charger', 'power' => '30W']],
            ['name' => 'Apple Pencil 2nd Gen', 'brand_index' => 0, 'price' => '129.99', 'rating' => '4.8', 'review_count' => 5678, 'description' => 'Precision stylus with pixel-perfect accuracy, tilt sensitivity, and double-tap gesture. Magnetically attaches and charges.', 'tags' => ['stylus', 'ipad', 'creative'], 'specs' => ['type' => 'Stylus', 'charging' => 'Magnetic wireless']],
            ['name' => 'Anker USB-C Hub 7-in-1', 'brand_index' => 19, 'price' => '39.99', 'rating' => '4.4', 'review_count' => 7654, 'description' => 'Expand your laptops connectivity with HDMI 4K, USB-A, USB-C, SD card reader, and 100W pass-through charging.', 'tags' => ['hub', 'usb-c', 'hdmi'], 'specs' => ['ports' => '7', 'hdmi' => '4K 30Hz']],
            ['name' => 'Apple Magic Keyboard', 'brand_index' => 0, 'price' => '299.99', 'rating' => '4.5', 'review_count' => 2345, 'description' => 'Full-size keyboard for iPad Pro with trackpad, backlit keys, and USB-C pass-through charging.', 'tags' => ['keyboard', 'ipad', 'trackpad'], 'specs' => ['type' => 'Keyboard Case', 'backlit' => 'Yes']],
            ['name' => 'Samsung SmartTag2', 'brand_index' => 1, 'price' => '29.99', 'rating' => '4.3', 'review_count' => 3456, 'description' => 'Smart tracker with compass view and lost mode. IP67 water resistant with up to 500 days battery life.', 'tags' => ['tracker', 'smartthings'], 'specs' => ['type' => 'Item Tracker', 'battery' => '500 days']],
            ['name' => 'Anker Soundcore Life Q35', 'brand_index' => 19, 'price' => '79.99', 'original_price' => '129.99', 'rating' => '4.4', 'review_count' => 5678, 'description' => 'LDAC Hi-Res wireless headphones with hybrid ANC. Multiple noise cancelling modes and 40-hour battery.', 'tags' => ['headphones', 'anc', 'budget'], 'specs' => ['type' => 'Over-ear', 'anc' => 'Yes', 'battery' => '40 hours']],
        ];

        // ========================================
        // MONITORS (category_index: 9)
        // ========================================
        $monitors = [
            ['name' => 'Apple Studio Display', 'brand_index' => 0, 'price' => '1599.99', 'rating' => '4.6', 'review_count' => 1890, 'description' => '27-inch 5K Retina display with A13 Bionic chip, 12MP Ultra Wide camera with Center Stage, and spatial audio.', 'tags' => ['5k', 'retina', 'thunderbolt'], 'specs' => ['size' => '27"', 'resolution' => '5K', 'panel' => 'IPS']],
            ['name' => 'Apple Pro Display XDR', 'brand_index' => 0, 'price' => '4999.99', 'rating' => '4.9', 'review_count' => 456, 'description' => '32-inch 6K Retina display with extreme dynamic range. Reference modes for professional HDR workflows.', 'tags' => ['6k', 'hdr', 'professional'], 'specs' => ['size' => '32"', 'resolution' => '6K', 'brightness' => '1600 nits']],
            ['name' => 'Dell UltraSharp U2723QE', 'brand_index' => 5, 'price' => '619.99', 'rating' => '4.7', 'review_count' => 2345, 'description' => '27-inch 4K USB-C hub monitor with IPS Black technology. 98% DCI-P3 color accuracy for professionals.', 'tags' => ['4k', 'usb-c', 'ips-black'], 'specs' => ['size' => '27"', 'resolution' => '4K', 'panel' => 'IPS Black']],
            ['name' => 'Dell S2722QC', 'brand_index' => 5, 'price' => '309.99', 'original_price' => '399.99', 'rating' => '4.5', 'review_count' => 4567, 'description' => '27-inch 4K USB-C monitor at an affordable price. 60W charging via USB-C and built-in speakers.', 'tags' => ['4k', 'usb-c', 'affordable'], 'specs' => ['size' => '27"', 'resolution' => '4K', 'panel' => 'IPS']],
            ['name' => 'LG UltraWide 34WN80C-B', 'brand_index' => 17, 'price' => '549.99', 'rating' => '4.5', 'review_count' => 2345, 'description' => '34-inch curved ultrawide with USB-C connectivity. 21:9 aspect ratio for immersive productivity.', 'tags' => ['ultrawide', 'curved', 'usb-c'], 'specs' => ['size' => '34"', 'resolution' => '3440x1440', 'panel' => 'IPS']],
            ['name' => 'LG 27GP850-B', 'brand_index' => 17, 'price' => '449.99', 'rating' => '4.6', 'review_count' => 3456, 'description' => '27-inch QHD Nano IPS gaming monitor. 165Hz refresh rate with 1ms response time and NVIDIA G-Sync.', 'tags' => ['gaming', '165hz', 'g-sync'], 'specs' => ['size' => '27"', 'resolution' => 'QHD', 'refresh' => '165Hz']],
            ['name' => 'Samsung Odyssey G7 32"', 'brand_index' => 1, 'price' => '599.99', 'original_price' => '799.99', 'rating' => '4.6', 'review_count' => 2890, 'description' => '32-inch curved QHD gaming monitor with 240Hz and 1ms response. QLED technology with deep blacks.', 'tags' => ['gaming', '240hz', 'curved', 'qled'], 'specs' => ['size' => '32"', 'resolution' => 'QHD', 'refresh' => '240Hz']],
            ['name' => 'Samsung M8 Smart Monitor', 'brand_index' => 1, 'price' => '699.99', 'rating' => '4.3', 'review_count' => 1567, 'description' => '32-inch 4K Smart Monitor with built-in streaming apps. Use as a monitor, TV, and smart hub.', 'tags' => ['smart-monitor', '4k', 'streaming'], 'specs' => ['size' => '32"', 'resolution' => '4K', 'smart' => 'Tizen']],
            ['name' => 'Asus ProArt Display PA279CRV', 'brand_index' => 11, 'price' => '449.99', 'rating' => '4.6', 'review_count' => 987, 'description' => '27-inch 4K creator monitor with 99% DCI-P3 and factory calibrated colors. USB-C 96W delivery.', 'tags' => ['creator', '4k', 'color-accurate'], 'specs' => ['size' => '27"', 'resolution' => '4K', 'color' => '99% DCI-P3']],
            ['name' => 'Lenovo ThinkVision P27h-30', 'brand_index' => 7, 'price' => '379.99', 'rating' => '4.4', 'review_count' => 1234, 'description' => '27-inch QHD monitor with USB-C docking. Natural low blue light and ergonomic stand for business.', 'tags' => ['business', 'qhd', 'usb-c'], 'specs' => ['size' => '27"', 'resolution' => 'QHD', 'panel' => 'IPS']],
        ];

        // Combine all categories
        $allProducts = [];
        $categoryMap = [
            0 => $smartphones,
            1 => $laptops,
            2 => $headphones,
            3 => $tablets,
            4 => $smartwatches,
            5 => $cameras,
            6 => $speakers,
            7 => $gaming,
            8 => $accessories,
            9 => $monitors,
        ];

        foreach ($categoryMap as $categoryIndex => $categoryProducts) {
            foreach ($categoryProducts as $product) {
                $product['category_index'] = $categoryIndex;
                $product['image_url'] = 'https://picsum.photos/seed/' . urlencode(str_replace(' ', '-', strtolower($product['name']))) . '/400/400';
                $allProducts[] = $product;
            }
        }

        // Generate additional variations to reach ~500
        $adjectives = ['Premium', 'Budget', 'Limited Edition', 'Special Edition', 'Refurbished'];
        $colors = ['Midnight Black', 'Silver', 'Space Gray', 'Blue', 'Rose Gold', 'White', 'Green'];

        $baseCount = count($allProducts);
        $targetExtra = 500 - $baseCount;

        for ($i = 0; $i < $targetExtra && $i < $baseCount; $i++) {
            $base = $allProducts[$i % $baseCount];
            $color = $colors[array_rand($colors)];
            $adj = $adjectives[array_rand($adjectives)];

            $variant = $base;
            $variant['name'] = $base['name'] . ' - ' . $color;
            $variant['price'] = (string) round((float) $base['price'] * (mt_rand(80, 120) / 100), 2);
            $variant['rating'] = (string) min(5.0, max(3.0, round((float) $base['rating'] + (mt_rand(-5, 5) / 10), 1)));
            $variant['review_count'] = max(10, $base['review_count'] + mt_rand(-500, 500));
            $variant['description'] = $color . ' edition. ' . $base['description'];
            $variant['original_price'] = null;
            if (mt_rand(0, 3) === 0) {
                $variant['original_price'] = (string) round((float) $variant['price'] * 1.2, 2);
            }
            $variant['image_url'] = 'https://picsum.photos/seed/' . urlencode(str_replace(' ', '-', strtolower($variant['name']))) . '/400/400';

            $allProducts[] = $variant;
        }

        return $allProducts;
    }
}
