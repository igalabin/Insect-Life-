-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 04, 2025 at 08:00 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `insect_life`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 3, 'user_logout', '{\"user_id\":3,\"username\":\"Zairee\",\"logout_time\":\"2025-09-24 16:05:16\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-24 14:05:16'),
(2, 3, 'user_logout', '{\"user_id\":3,\"username\":\"Zairee\",\"logout_time\":\"2025-09-24 16:11:03\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-24 14:11:03'),
(3, 3, 'user_logout', '{\"user_id\":3,\"username\":\"Zairee\",\"logout_time\":\"2025-09-24 19:08:49\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-24 17:08:49');

-- --------------------------------------------------------

--
-- Table structure for table `articles`
--

CREATE TABLE `articles` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `content` longtext NOT NULL,
  `excerpt` text DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `scientific_name` varchar(100) DEFAULT NULL,
  `common_names` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`common_names`)),
  `insect_order` varchar(50) DEFAULT NULL,
  `insect_family` varchar(50) DEFAULT NULL,
  `category` enum('anatomy','behavior','ecology','conservation','identification','general') DEFAULT 'general',
  `difficulty_level` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `estimated_read_time` int(11) DEFAULT 5,
  `author_id` int(11) NOT NULL,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `meta_description` text DEFAULT NULL,
  `meta_keywords` text DEFAULT NULL,
  `views_count` int(11) DEFAULT 0,
  `published_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `articles`
--

INSERT INTO `articles` (`id`, `title`, `slug`, `content`, `excerpt`, `featured_image`, `scientific_name`, `common_names`, `insect_order`, `insect_family`, `category`, `difficulty_level`, `estimated_read_time`, `author_id`, `status`, `tags`, `meta_description`, `meta_keywords`, `views_count`, `published_at`, `created_at`, `updated_at`) VALUES
(537, 'The Complete Butterfly Life Cycle', 'butterfly-life-cycle', '<h2>Understanding Butterfly Metamorphosis</h2>\r\n<p>Butterflies undergo one of nature\'s most remarkable transformations through a process called complete metamorphosis. This fascinating journey consists of four distinct stages, each serving a crucial purpose in the butterfly\'s development.</p>\r\n\r\n<h3>Stage 1: The Egg</h3>\r\n<p>The butterfly life cycle begins when a female butterfly lays her eggs on a carefully selected host plant. These tiny eggs, often smaller than a pinhead, are strategically placed on plants that will provide food for the emerging caterpillar. The egg stage typically lasts 3-5 days, though this varies by species and environmental conditions.</p>\r\n\r\n<h3>Stage 2: The Larva (Caterpillar)</h3>\r\n<p>When the egg hatches, a tiny caterpillar emerges. This is the larval stage, and it\'s dedicated entirely to eating and growing. Caterpillars have powerful jaws designed for chewing leaves. As they grow, they molt several times, shedding their skin to accommodate their increasing size. This stage usually lasts 3-5 weeks.</p>\r\n\r\n<h3>Stage 3: The Pupa (Chrysalis)</h3>\r\n<p>After reaching full size, the caterpillar enters the pupal stage by forming a chrysalis. Inside this protective casing, the caterpillar\'s body completely reorganizes. Cells break down and reform into adult butterfly structures - wings, legs, antennae, and more. This remarkable transformation takes 1-2 weeks for most species.</p>\r\n\r\n<h3>Stage 4: The Adult Butterfly</h3>\r\n<p>Finally, the adult butterfly emerges from the chrysalis. Its wings are soft and crumpled at first, but as the butterfly pumps fluid into them, they expand and harden. Once the wings are dry and strong, the butterfly can fly and begin its adult life of feeding, mating, and reproducing.</p>\r\n\r\n<h2>Fascinating Facts</h2>\r\n<p>The entire life cycle from egg to adult typically takes about one month, though some species take much longer. Butterflies that overwinter as adults can live for several months, while others live just a few weeks.</p>', 'Learn about the four stages of butterfly metamorphosis: egg, larva, pupa, and adult. Understand this remarkable transformation process.', 'uploads/articles/article_1759593029_68e1424506d00.webp', 'Danaus plexippus', NULL, 'lepidoptera', 'various', 'anatomy', 'beginner', 8, 3, 'published', NULL, NULL, NULL, 0, '2025-10-04 23:09:47', '2025-10-04 15:09:47', '2025-10-04 15:50:29'),
(538, 'Butterfly Anatomy: Structure and Function', 'butterfly-anatomy-structure', '<h2>The Remarkable Body of a Butterfly</h2>\r\n<p>Butterflies possess a unique body structure perfectly adapted for their lifestyle. Like all insects, butterflies have three main body parts: the head, thorax, and abdomen.</p>\r\n\r\n<h3>The Head and Sensory Organs</h3>\r\n<p>A butterfly\'s head houses its most important sensory organs. The large compound eyes provide nearly 360-degree vision and can see colors including ultraviolet light invisible to humans. Two antennae extend from the head, serving as sensory organs for smell and balance.</p>\r\n\r\n<h3>Six Legs and Taste Receptors</h3>\r\n<p>All butterflies have six legs attached to the thorax, though some species hold their front legs close to their body, making them appear four-legged. Remarkably, butterflies taste with their feet! When they land on a flower, taste receptors in their feet help them determine if it\'s suitable for feeding or egg-laying.</p>\r\n\r\n<h3>Wings and Scales</h3>\r\n<p>Perhaps the most striking feature of butterflies are their wings. Each butterfly has four wings covered in thousands of tiny scales that overlap like roof tiles. These scales create the butterfly\'s colors and patterns through pigmentation and light refraction. The scales also provide temperature regulation and protection from water.</p>\r\n\r\n<h3>The Proboscis: A Specialized Feeding Tube</h3>\r\n<p>Adult butterflies feed primarily on flower nectar using a long, straw-like tongue called a proboscis. When not in use, the proboscis coils up like a watch spring beneath the head. To feed, the butterfly uncoils it and inserts it into flowers to sip nectar.</p>\r\n\r\n<h2>Adaptations for Survival</h2>\r\n<p>Every aspect of butterfly anatomy serves a purpose. Their light weight and large wing surface area enable efficient flight. Their proboscis allows them to access nectar deep within flowers. Their taste receptors ensure they find the right plants for their offspring.</p>', 'Explore the fascinating anatomy of butterflies, from their compound eyes and taste-sensing feet to their colorful scaled wings.', 'uploads/articles/article_1759591803_68e13d7b74baa.webp', 'Papilio machaon', NULL, 'lepidoptera', 'various', 'anatomy', 'beginner', 7, 3, 'published', NULL, NULL, NULL, 0, '2025-10-04 23:09:47', '2025-10-04 15:09:47', '2025-10-04 15:30:03'),
(539, 'Coleoptera: The Magnificent Beetles', 'coleoptera-magnificent-beetles', '<h2>The World\'s Most Diverse Order</h2>\r\n<p>Beetles belong to the order Coleoptera, a name derived from Greek words meaning \"sheath wing.\" This refers to their distinctive hardened front wings called elytra. With over 400,000 described species, beetles represent approximately 40% of all known insect species and 25% of all animal species on Earth.</p>\r\n\r\n<h3>What Defines a Beetle?</h3>\r\n<p>All beetles share certain characteristics. The most distinctive feature is their elytra - hardened front wings that meet in a straight line down the middle of the back. These protective wing covers shield the delicate flying wings folded beneath. When a beetle flies, it lifts its elytra and unfolds its membranous hind wings.</p>\r\n\r\n<h3>Incredible Diversity</h3>\r\n<p>Beetles have adapted to almost every terrestrial and freshwater habitat on Earth. They range in size from tiny feather-winged beetles less than 1mm long to massive titan beetles reaching 17cm. They occupy every ecological role: predators, herbivores, decomposers, parasites, and pollinators.</p>\r\n\r\n<h3>Major Beetle Families</h3>\r\n<p>Some notable beetle families include: Carabidae (ground beetles) with over 40,000 species; Scarabaeidae (scarab beetles) including dung beetles; Cerambycidae (longhorn beetles) known for their long antennae; and Coccinellidae (ladybugs) beloved as garden predators.</p>\r\n\r\n<h2>Ecological Importance</h2>\r\n<p>Beetles play crucial roles in ecosystems. Dung beetles recycle waste and improve soil. Predatory beetles control pest populations. Wood-boring beetles help decompose dead trees. Many beetles pollinate flowers, particularly in tropical regions.</p>', 'Discover Coleoptera, the largest order of insects. Learn about beetle diversity, anatomy, and ecological importance.', 'uploads/articles/article_1759590679_68e13917475de.webp', 'Dynastes hercules', NULL, 'coleoptera', 'various', 'identification', 'intermediate', 10, 3, 'published', NULL, NULL, NULL, 12, '2025-10-04 23:09:48', '2025-10-04 15:09:48', '2025-10-04 17:59:10'),
(540, 'Beneficial Beetles: Natures Pest Controllers', 'beneficial-beetles-pest-control', '<h2>Beetles as Garden Allies</h2>\r\n<p>Not all beetles are pests - many are incredibly beneficial insects that help control garden pests naturally. Understanding which beetles to encourage can reduce the need for chemical pesticides.</p>\r\n\r\n<h3>Ladybugs: The Ultimate Aphid Predator</h3>\r\n<p>Ladybugs, also called lady beetles or ladybird beetles (family Coccinellidae), are among the most beneficial beetles. A single ladybug can eat up to 5,000 aphids in its lifetime. Both larvae and adults feed on aphids, scale insects, mites, and other soft-bodied pests. There are over 5,000 species worldwide.</p>\r\n\r\n<h3>Ground Beetles: Nocturnal Hunters</h3>\r\n<p>Ground beetles (family Carabidae) are excellent predators of slugs, snails, caterpillars, and other garden pests. These beetles are typically active at night, hunting on the ground. They\'re recognized by their long legs and fast running ability.</p>\r\n\r\n<h3>Rove Beetles: Cleanup Crew</h3>\r\n<p>Rove beetles (family Staphylinidae) are small, elongated beetles that prey on various pest insects and their eggs. They\'re particularly effective against fungus gnats, root maggots, and other soil-dwelling pests.</p>\r\n\r\n<h2>Attracting Beneficial Beetles</h2>\r\n<p>To encourage beneficial beetles in your garden: provide ground cover and mulch for shelter, plant diverse flowers for nectar, avoid broad-spectrum pesticides, and create beetle habitats with rocks and logs.</p>', 'Learn about beneficial beetles like ladybugs and ground beetles that provide natural pest control in gardens and agriculture.', 'uploads/articles/article_1759592505_68e140390798f.webp', 'Coccinella septempunctata', NULL, 'coleoptera', 'coccinellidae', 'ecology', 'beginner', 6, 3, 'published', NULL, NULL, NULL, 1, '2025-10-04 23:09:48', '2025-10-04 15:09:48', '2025-10-04 17:57:22'),
(541, 'Inside the Ant Colony: Social Structure and Organization', 'ant-colony-social-structure', '<h2>The Superorganism</h2>\r\n<p>An ant colony functions like a single organism, with thousands or millions of individuals working together in remarkable coordination. This social structure has made ants one of the most successful groups of animals on Earth.</p>\r\n\r\n<h3>The Three Castes</h3>\r\n<p>Most ant species have three distinct castes. The queen, usually much larger than other ants, is the colony\'s reproductive female. Her primary role is laying eggs - sometimes thousands per day. Queens can live for decades, with some species\' queens living over 30 years.</p>\r\n\r\n<p>Worker ants are sterile females that perform all colony tasks: foraging, caring for young, maintaining the nest, and defending the colony. Workers typically live a few months to a year. They represent 90-95% of the colony population.</p>\r\n\r\n<p>Males exist solely for reproduction. They develop from unfertilized eggs, mate with new queens during nuptial flights, and die shortly after mating. Males are only produced when the colony is ready to reproduce.</p>\r\n\r\n<h3>Communication Through Chemistry</h3>\r\n<p>Ants communicate primarily through chemical signals called pheromones. Trail pheromones help ants find food sources and navigate. Alarm pheromones alert the colony to danger. Recognition pheromones identify nestmates from intruders. This chemical language allows complex coordination without central control.</p>\r\n\r\n<h3>Division of Labor</h3>\r\n<p>Worker ants perform different tasks based on their age, size, and colony needs. Young workers typically care for the queen and larvae. Middle-aged workers maintain the nest and process food. Older workers venture outside to forage and defend. This age-based division of labor is called temporal polyethism.</p>\r\n\r\n<h2>Collective Intelligence</h2>\r\n<p>Despite individual simplicity, ant colonies exhibit complex problem-solving abilities through collective intelligence. This swarm intelligence allows colonies to find optimal food routes, regulate nest temperature, and respond to environmental changes.</p>', 'Explore the fascinating social structure of ant colonies, from queens and workers to chemical communication and collective intelligence.', 'uploads/articles/article_1759592581_68e14085edc3c.webp', 'Formica rufa', NULL, 'hymenoptera', 'formicidae', 'behavior', 'intermediate', 9, 3, 'published', NULL, NULL, NULL, 0, '2025-10-04 23:09:48', '2025-10-04 15:09:48', '2025-10-04 15:43:01'),
(542, 'Ant Farming: Leafcutter Ants and Fungus Gardens', 'leafcutter-ants-fungus-farming', '<h2>Nature\'s First Farmers</h2>\r\n<p>Long before humans developed agriculture, leafcutter ants were cultivating crops. These remarkable insects have been farming fungus for over 50 million years, creating one of nature\'s most sophisticated agricultural systems.</p>\r\n\r\n<h3>How Leafcutter Ants Farm</h3>\r\n<p>Leafcutter ants don\'t eat the leaves they cut. Instead, they use the leaf fragments as compost to grow fungus in underground gardens. Worker ants cut leaf pieces, carry them to the nest (sometimes weighing 20 times their body weight), chew them into pulp, and place them in fungus gardens.</p>\r\n\r\n<h3>The Fungus Garden</h3>\r\n<p>The fungus (genus Leucoagaricus) grows on this leaf mulch, producing specialized structures called gongylidia that the ants eat. This fungus exists nowhere else in nature - it\'s been domesticated by ants for millions of years and can\'t survive without ant cultivation.</p>\r\n\r\n<h3>Colony Agriculture</h3>\r\n<p>Different worker castes perform specific farming tasks. Small workers tend the gardens, weeding out unwanted fungi and bacteria. Medium workers cut and transport leaves. Large soldiers defend the foraging trails and nest. The ants even use antibiotics - they culture bacteria that produce antifungal compounds to protect their crops.</p>\r\n\r\n<h2>Ecological Impact</h2>\r\n<p>Leafcutter colonies can contain 8 million ants and consume as much vegetation as a cow. Their farming activities significantly impact tropical forest ecosystems, affecting nutrient cycling and plant communities.</p>', 'Discover how leafcutter ants cultivate fungus gardens in a complex agricultural system millions of years in the making.', 'uploads/articles/article_1759591741_68e13d3ddb3fd.webp', 'Atta cephalotes', NULL, 'hymenoptera', 'formicidae', 'behavior', 'intermediate', 7, 3, 'published', NULL, NULL, NULL, 0, '2025-10-04 23:09:48', '2025-10-04 15:09:48', '2025-10-04 15:29:01'),
(543, 'Insects as Pollinators: The Foundation of Food Security', 'insects-pollinators-food-security', '<h2>The Pollination Partnership</h2>\r\n<p>Insects are the world\'s most important pollinators, responsible for pollinating approximately 90% of flowering plant species. This ecological service is essential for both wild ecosystems and human agriculture.</p>\r\n\r\n<h3>How Insect Pollination Works</h3>\r\n<p>When insects visit flowers to feed on nectar or collect pollen, they inadvertently transfer pollen from the male parts (anthers) to the female parts (stigma) of flowers. This process enables fertilization and seed production. The relationship between insects and flowering plants has evolved over 100 million years.</p>\r\n\r\n<h3>Major Insect Pollinators</h3>\r\n<p>Bees are the most important pollinators, with over 20,000 species worldwide. Honeybees, bumblebees, and solitary bees all play crucial roles. Butterflies and moths pollinate while feeding on nectar. Flies, particularly hoverflies, pollinate many crops and wildflowers. Even beetles pollinate certain plant species, especially in tropical regions.</p>\r\n\r\n<h3>Agricultural Importance</h3>\r\n<p>About 35% of global food production depends on animal pollinators, primarily insects. Crops requiring insect pollination include apples, almonds, blueberries, cucumbers, and coffee. The economic value of insect pollination is estimated at hundreds of billions of dollars annually.</p>\r\n\r\n<h2>Threats to Pollinators</h2>\r\n<p>Pollinator populations face serious threats including habitat loss, pesticide use (especially neonicotinoids), climate change, and diseases. Declining pollinator populations threaten both wild ecosystems and food security.</p>', 'Learn about the critical role insects play as pollinators and why protecting them is essential for ecosystems and agriculture.', 'uploads/articles/article_1759592395_68e13fcb9fcc4.webp', 'Apis mellifera', NULL, '', 'various', 'ecology', 'beginner', 8, 3, 'published', NULL, NULL, NULL, 1, '2025-10-04 23:09:48', '2025-10-04 15:09:48', '2025-10-04 17:48:12'),
(544, 'Nature&#039;s Recyclers: Decomposer Insects', 'decomposer-insects-recycling', '<h2>Essential Ecosystem Engineers</h2>\r\n<p>While less glamorous than pollinators or predators, decomposer insects perform the vital task of breaking down dead organic matter and recycling nutrients back into ecosystems.</p>\r\n\r\n<h3>Carrion Beetles and Flies</h3>\r\n<p>When an animal dies, carrion beetles and blow flies are often the first to arrive. Carrion beetles (family Silphidae) bury small animal carcasses as food for their larvae. Blow fly larvae (maggots) consume decaying flesh rapidly, cleaning up carcasses in days or weeks.</p>\r\n\r\n<h3>Dung Beetles: Waste Management Specialists</h3>\r\n<p>Dung beetles (family Scarabaeidae) are nature\'s sanitation workers. They locate animal dung, roll it into balls, and bury it to feed their larvae. This behavior removes waste, reduces disease-spreading flies, improves soil structure, and recycles nutrients. A single cow pat can attract thousands of dung beetles.</p>\r\n\r\n<h3>Wood-Boring Beetles and Termites</h3>\r\n<p>These insects break down dead wood, returning nutrients from fallen trees to the soil. Termites are particularly important in tropical ecosystems, processing huge amounts of cellulose. Wood-boring beetle larvae tunnel through dead trees, creating pathways for fungi and bacteria that further decompose the wood.</p>\r\n\r\n<h2>Nutrient Cycling</h2>\r\n<p>Without decomposer insects, dead plant and animal matter would accumulate, locking up nutrients. These insects accelerate decomposition, making nutrients available to plants and maintaining ecosystem productivity.</p>', 'Discover how beetles, flies, and other decomposer insects recycle organic matter and maintain ecosystem health.', 'uploads/articles/article_1759592433_68e13ff106e95.webp', 'Nicrophorus americanus', NULL, '', 'various', 'ecology', 'beginner', 6, 3, 'published', NULL, NULL, NULL, 0, '2025-10-04 23:09:48', '2025-10-04 15:09:48', '2025-10-04 15:40:33'),
(545, 'Dragonfly Flight: Masters of the Air', 'dragonfly-flight-masters', '<h2>Aerial Acrobats</h2>\r\n<p>Dragonflies are among the most skilled fliers in the insect world. Their unique wing structure and flight muscles enable aerial maneuvers that have inspired engineers and aircraft designers.</p>\r\n\r\n<h3>Four Independent Wings</h3>\r\n<p>Unlike most flying insects, dragonflies have four wings that move independently. This allows unprecedented control. They can adjust the angle and timing of each wing separately, enabling them to hover motionless, fly backwards, change direction instantly, and even fly upside down briefly.</p>\r\n\r\n<h3>Speed and Agility</h3>\r\n<p>Dragonflies can reach speeds of 35 mph, making them among the fastest flying insects. They can accelerate rapidly and make sharp turns at high speeds. This agility makes them extremely effective predators - some species have hunting success rates exceeding 95%.</p>\r\n\r\n<h3>Wing Structure</h3>\r\n<p>Dragonfly wings are marvels of engineering. The wings are supported by a complex network of veins that provide strength while remaining lightweight. A small thickening on the leading edge of each wing, called a pterostigma, acts as a weight that helps prevent destructive vibrations during flight.</p>\r\n\r\n<h2>Flight Muscles</h2>\r\n<p>Dragonflies have powerful direct flight muscles attached to the wing bases. This gives them precise control but requires significant energy. They\'re ectothermic (cold-blooded) and often bask in the sun to warm their flight muscles before hunting.</p>', 'Explore the remarkable flight capabilities of dragonflies, from their four independent wings to their incredible speed and maneuverability.', 'uploads/articles/article_1759592627_68e140b3a148a.webp', 'Anax junius', NULL, '', 'various', 'anatomy', 'intermediate', 7, 3, 'published', NULL, NULL, NULL, 0, '2025-10-04 23:09:48', '2025-10-04 15:09:48', '2025-10-04 15:43:47'),
(546, 'Dragonfly Eyes: Nearly 360-Degree Vision', 'dragonfly-eyes-vision', '<h2>The Ultimate Visual Predators</h2>\r\n<p>Dragonflies possess some of the most sophisticated visual systems in the insect world. Their enormous compound eyes provide exceptional visual capabilities that make them supreme aerial hunters.</p>\r\n\r\n<h3>Massive Compound Eyes</h3>\r\n<p>A dragonfly\'s head is almost entirely dominated by its two compound eyes. Each eye contains up to 30,000 individual lenses (ommatidia), providing nearly 360-degree vision. The eyes wrap around the head, creating a visual field that covers almost all directions simultaneously.</p>\r\n\r\n<h3>Color and Polarized Light</h3>\r\n<p>Dragonflies see a broader spectrum of colors than humans, including ultraviolet light. They can also detect polarized light, helping them navigate and identify water surfaces for egg-laying. This enhanced color vision helps them spot prey and mates.</p>\r\n\r\n<h3>Motion Detection</h3>\r\n<p>Dragonfly eyes are particularly sensitive to movement. They can detect the motion of tiny prey insects against complex backgrounds. Different regions of their eyes specialize in different tasks - the upper region detects small moving objects against the sky, while the lower region focuses on larger, nearby objects.</p>\r\n\r\n<h2>Hunting by Sight</h2>\r\n<p>Dragonflies are visual hunters, relying almost entirely on their eyes to catch prey. They can track multiple prey items simultaneously and calculate interception courses while flying at high speed.</p>', 'Learn about dragonflies&#039; extraordinary compound eyes that provide nearly 360-degree vision and exceptional prey-detection capabilities.', 'uploads/articles/article_1759592659_68e140d3ee1e8.webp', 'Libellula luctuosa', NULL, '', 'various', 'anatomy', 'intermediate', 6, 3, 'published', NULL, NULL, NULL, 0, '2025-10-04 23:09:48', '2025-10-04 15:09:48', '2025-10-04 15:44:19'),
(547, 'Colony Collapse Disorder: Understanding the Bee Crisis', 'colony-collapse-disorder-bee-crisis', '<h2>A Modern Mystery</h2>\r\n<p>Colony Collapse Disorder (CCD) emerged as a major concern in 2006 when beekeepers began reporting massive losses of honeybee colonies. This phenomenon threatens both wild ecosystems and agricultural production.</p>\r\n\r\n<h3>What is Colony Collapse Disorder?</h3>\r\n<p>CCD occurs when the majority of worker bees in a colony suddenly disappear, leaving behind the queen, plenty of food, and a few nurse bees caring for remaining immature bees. Unlike normal colony losses, the bees simply vanish without leaving dead bodies near the hive.</p>\r\n\r\n<h3>Multiple Causes</h3>\r\n<p>Research suggests CCD results from a combination of stressors rather than a single cause. Key factors include: parasitic Varroa mites that weaken bees and spread viruses; pesticides, particularly neonicotinoids that impair bee navigation and immune function; poor nutrition from reduced wildflower diversity; diseases and pathogens; and climate change affecting flower timing.</p>\r\n\r\n<h3>Impact on Agriculture</h3>\r\n<p>Honeybees pollinate about one-third of the food we eat. CCD and general bee decline threaten production of almonds, apples, berries, and many other crops. Some regions now require commercial beekeepers to transport hives long distances to pollinate crops.</p>\r\n\r\n<h2>Solutions and Hope</h2>\r\n<p>Addressing CCD requires multiple approaches: reducing pesticide use, increasing habitat diversity, treating parasites and diseases, and supporting diverse wild bee populations that can supplement honeybee pollination.</p>', 'Understand Colony Collapse Disorder, its causes, and why it threatens both agriculture and natural ecosystems.', 'uploads/articles/article_1759592738_68e14122ecff4.webp', 'Apis mellifera', NULL, 'hymenoptera', 'apidae', 'conservation', 'intermediate', 8, 3, 'published', NULL, NULL, NULL, 0, '2025-10-04 23:09:48', '2025-10-04 15:09:48', '2025-10-04 15:45:38'),
(548, 'Protecting Pollinators: How You Can Help', 'protecting-pollinators-help', '<h2>Everyone Can Support Bees</h2>\r\n<p>While bee populations face serious threats, individual actions can make a real difference in supporting these crucial pollinators.</p>\r\n\r\n<h3>Plant Native Flowers</h3>\r\n<p>Native plants provide the best food sources for local bee species. Create diverse plantings that bloom throughout the growing season. Excellent choices include sunflowers, lavender, coneflowers, and native wildflowers. Group the same plants together to make them easier for bees to find.</p>\r\n\r\n<h3>Avoid Pesticides</h3>\r\n<p>Pesticides, especially neonicotinoids, harm bees even at low doses. These chemicals can impair bee navigation, reproduction, and immune systems. Choose organic pest management methods or use targeted, bee-safe alternatives when pest control is necessary.</p>\r\n\r\n<h3>Provide Nesting Sites</h3>\r\n<p>Many native bees are solitary and nest in holes in wood, hollow stems, or bare ground. Leave some areas of bare soil for ground-nesting bees. Create bee hotels with drilled wooden blocks. Leave dead tree snags and hollow stems standing over winter.</p>\r\n\r\n<h3>Support Local Beekeepers</h3>\r\n<p>Buy local honey to support beekeepers who maintain healthy hives. Many beekeepers use sustainable practices and support wild bee conservation efforts.</p>\r\n\r\n<h2>Native Bees Matter Too</h2>\r\n<p>Remember that native wild bees are often better pollinators of native plants than honeybees. Protecting diverse bee species strengthens overall ecosystem health.</p>', 'Practical steps you can take to support bee populations: planting native flowers, avoiding pesticides, and creating habitat.', 'uploads/articles/article_1759592797_68e1415d40cae.webp', 'Bombus impatiens', NULL, 'hymenoptera', 'apidae', 'conservation', 'beginner', 6, 3, 'published', NULL, NULL, NULL, 0, '2025-10-04 23:09:48', '2025-10-04 15:09:48', '2025-10-04 15:46:37'),
(549, 'Mosquito Life Cycle and Biology', 'mosquito-life-cycle-biology', '<h2>Understanding Mosquitoes</h2>\r\n<p>Mosquitoes are among the most medically important insects, transmitting diseases that affect millions of people annually. Understanding their biology is crucial for effective control.</p>\r\n\r\n<h3>Why Females Bite</h3>\r\n<p>Only female mosquitoes bite humans and animals. They need blood meals to develop their eggs - blood provides protein necessary for egg production. Males, by contrast, feed exclusively on nectar and plant juices. After a blood meal, females can develop 100-300 eggs.</p>\r\n\r\n<h3>Aquatic Development</h3>\r\n<p>All mosquitoes require water to complete their life cycle. Females lay eggs in or near water. The eggs hatch into aquatic larvae (wigglers) that filter-feed on microorganisms. After four larval stages, they transform into comma-shaped pupae (tumblers). After 1-4 days, adult mosquitoes emerge from the pupal case.</p>\r\n\r\n<h3>Finding Hosts</h3>\r\n<p>Mosquitoes locate hosts using multiple cues. Carbon dioxide from breath can be detected from 100 feet away. Body heat, movement, and body odors (especially lactic acid) help mosquitoes pinpoint targets. Dark colors attract mosquitoes more than light colors.</p>\r\n\r\n<h2>Disease Transmission</h2>\r\n<p>Mosquitoes transmit diseases by transferring pathogens from infected to uninfected hosts through their saliva. Major mosquito-borne diseases include malaria (killing over 400,000 annually), dengue fever, yellow fever, Zika virus, and West Nile virus.</p>', 'Learn about mosquito biology, their life cycle, why females bite, and how they locate hosts and transmit diseases.', 'uploads/articles/article_1759592830_68e1417e9437e.webp', 'Aedes aegypti', NULL, 'diptera', 'culicidae', 'behavior', 'advanced', 9, 3, 'published', NULL, NULL, NULL, 0, '2025-10-04 23:09:48', '2025-10-04 15:09:48', '2025-10-04 15:47:10'),
(550, 'Mosquito Control: Breaking the Life Cycle', 'mosquito-control-methods', '<h2>Integrated Mosquito Management</h2>\r\n<p>Effective mosquito control requires understanding and interrupting their life cycle at vulnerable points. Integrated approaches combine multiple strategies for best results.</p>\r\n\r\n<h3>Eliminating Breeding Sites</h3>\r\n<p>Since mosquitoes require water to develop, removing standing water eliminates breeding sites. Empty containers, clean gutters, change pet water daily, and maintain swimming pools properly. Even small amounts of water in bottle caps or plant saucers can support mosquito development.</p>\r\n\r\n<h3>Biological Control</h3>\r\n<p>Mosquito fish (Gambusia) eat mosquito larvae in ponds. Bacillus thuringiensis israelensis (Bti) is a bacteria that kills mosquito larvae but is harmless to other organisms. Dragonfly nymphs and other aquatic predators naturally control mosquito populations.</p>\r\n\r\n<h3>Personal Protection</h3>\r\n<p>Use EPA-registered insect repellents containing DEET, picaridin, or oil of lemon eucalyptus. Wear long sleeves and pants during peak mosquito hours (dawn and dusk). Use bed nets in areas with malaria or dengue. Install window screens to keep mosquitoes out.</p>\r\n\r\n<h2>Community Efforts</h2>\r\n<p>Large-scale mosquito control often involves community-wide efforts: larvicide application to standing water, adult mosquito spraying in high-risk areas, and public education about prevention methods.</p>', 'Discover effective mosquito control strategies from eliminating breeding sites to biological control and personal protection methods.', 'uploads/articles/article_1759592863_68e1419f199a2.webp', 'Culex pipiens', NULL, 'diptera', 'culicidae', 'conservation', 'intermediate', 7, 3, 'published', NULL, NULL, NULL, 0, '2025-10-04 23:09:48', '2025-10-04 15:09:48', '2025-10-04 15:47:43'),
(551, 'Grasshoppers vs Crickets: Key Identification Features', 'grasshoppers-crickets-identification', '<h2>Distinguishing Orthopterans</h2>\r\n<p>Grasshoppers, crickets, and katydids all belong to the order Orthoptera, but they have distinct differences that make identification straightforward once you know what to look for.</p>\r\n\r\n<h3>Antenna Length</h3>\r\n<p>The easiest way to distinguish grasshoppers from crickets is antenna length. Grasshoppers have short, thick antennae typically shorter than their body. Crickets and katydids have long, thread-like antennae often exceeding their body length. This difference reflects their different sensory needs.</p>\r\n\r\n<h3>Body Shape and Size</h3>\r\n<p>Grasshoppers typically have robust, cylindrical bodies adapted for jumping and daytime activity. Crickets are usually more compact with somewhat flattened bodies. Katydids resemble large green crickets and often have leaf-like wing modifications.</p>\r\n\r\n<h3>Activity Patterns</h3>\r\n<p>Grasshoppers are diurnal (day-active) and rely heavily on vision. They bask in sunlight and are most active during warm days. Crickets are primarily nocturnal (night-active), hiding during the day and emerging after dark. Their long antennae help them navigate in darkness.</p>\r\n\r\n<h3>Sound Production</h3>\r\n<p>Male crickets produce their characteristic chirps by rubbing their wings together, with structures on one wing scraping against ridges on the other. Grasshoppers create sound by rubbing their hind legs against their wings. Each species has a distinct sound pattern used to attract mates.</p>\r\n\r\n<h2>Ecological Roles</h2>\r\n<p>Both groups are important herbivores in grassland ecosystems. Grasshoppers can become agricultural pests during outbreak years, while crickets are often beneficial, eating pest insect eggs and decaying matter.</p>', 'Learn to identify grasshoppers, crickets, and katydids by examining antennae, body shape, behavior, and sound production.', 'uploads/articles/article_1759592896_68e141c04a866.webp', 'Melanoplus differentialis', NULL, 'orthoptera', 'various', 'identification', 'beginner', 7, 3, 'published', NULL, NULL, NULL, 0, '2025-10-04 23:09:48', '2025-10-04 15:09:48', '2025-10-04 15:48:16'),
(552, 'Locusts: When Grasshoppers Swarm', 'locusts-grasshopper-swarms', '<h2>From Solitary to Gregarious</h2>\r\n<p>Locusts are not a separate species from grasshoppers - they are grasshoppers that undergo a dramatic behavioral and physical transformation in response to crowding. This phenomenon has caused devastating agricultural losses throughout human history.</p>\r\n\r\n<h3>The Phase Transformation</h3>\r\n<p>Under normal conditions, certain grasshopper species exist in a \"solitary phase,\" avoiding each other and living independently. However, when environmental conditions like rainfall create abundant vegetation, populations explode. As density increases and food becomes scarce, physical contact triggers a remarkable transformation to the \"gregarious phase.\"</p>\r\n\r\n<h3>Physical and Behavioral Changes</h3>\r\n<p>Gregarious phase locusts look and act completely different from their solitary counterparts. They change color (often becoming darker or brighter), develop longer wings, grow larger bodies, and most importantly, become strongly attracted to other locusts instead of avoiding them. These changes are triggered by serotonin release in response to repeated touching of their hind legs.</p>\r\n\r\n<h3>Swarm Formation</h3>\r\n<p>Gregarious locusts form massive swarms that can contain billions of individuals and cover hundreds of square miles. These swarms can travel 100+ miles per day, consuming their body weight in vegetation daily. A single large swarm can eat as much food as 35,000 people in one day.</p>\r\n\r\n<h3>Historical Impact</h3>\r\n<p>Locust plagues appear in ancient texts including the Bible and Egyptian records. Desert locusts (Schistocerca gregaria) remain a major threat in Africa, the Middle East, and Asia. Modern monitoring and control efforts use satellite imagery and pesticides to track and suppress swarm formation.</p>\r\n\r\n<h2>Climate and Outbreak Patterns</h2>\r\n<p>Climate patterns strongly influence locust outbreaks. Unusual rainfall in normally dry areas can trigger population explosions. Climate change may alter outbreak frequency and distribution, creating new challenges for agriculture in affected regions.</p>', 'Understand how grasshoppers transform into swarming locusts and why these phase changes create one of agriculture&#039;s greatest challenges.', 'uploads/articles/article_1759592923_68e141db7e10c.webp', 'Schistocerca gregaria', NULL, 'orthoptera', 'acrididae', 'behavior', 'intermediate', 8, 3, 'published', NULL, NULL, NULL, 0, '2025-10-04 23:09:48', '2025-10-04 15:09:48', '2025-10-04 15:48:43');

-- --------------------------------------------------------

--
-- Table structure for table `article_images`
--

CREATE TABLE `article_images` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `caption` text DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(50) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `article_ratings`
--

CREATE TABLE `article_ratings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `rating` tinyint(1) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `review` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `article_ratings`
--

INSERT INTO `article_ratings` (`id`, `user_id`, `article_id`, `rating`, `review`, `created_at`, `updated_at`) VALUES
(2, 3, 539, 1, NULL, '2025-10-04 17:47:56', '2025-10-04 17:47:56');

-- --------------------------------------------------------

--
-- Table structure for table `assessments`
--

CREATE TABLE `assessments` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `difficulty_level` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `total_questions` int(11) NOT NULL,
  `question_count` int(11) DEFAULT 0,
  `passing_score` decimal(5,2) DEFAULT 70.00,
  `time_limit` int(11) DEFAULT NULL,
  `badge_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assessments`
--

INSERT INTO `assessments` (`id`, `title`, `description`, `category`, `difficulty_level`, `total_questions`, `question_count`, `passing_score`, `time_limit`, `badge_id`, `is_active`, `created_at`, `updated_at`) VALUES
(81, 'Introduction to Butterflies', 'Test your basic knowledge about butterfly anatomy, behavior, and lifecycle.', 'anatomy', 'beginner', 0, 5, 70.00, 15, NULL, 1, '2025-10-04 14:41:01', '2025-10-04 14:41:01'),
(82, 'Beetle Diversity and Classification', 'Explore the amazing diversity of beetles, the largest order of insects.', 'identification', 'intermediate', 0, 5, 75.00, 20, NULL, 1, '2025-10-04 14:41:02', '2025-10-04 14:41:02'),
(83, 'Ant Colonies and Social Structure', 'Learn about the fascinating social organization of ant colonies.', 'behavior', 'intermediate', 0, 6, 70.00, 25, 5, 1, '2025-10-04 14:41:02', '2025-10-04 14:47:56'),
(84, 'Insects in Ecosystems', 'Understand the critical roles insects play in ecosystem functioning.', 'ecology', 'beginner', 0, 5, 65.00, 20, NULL, 1, '2025-10-04 14:41:02', '2025-10-04 14:41:02'),
(85, 'Dragonfly Structure and Flight', 'Discover the unique anatomy and flight capabilities of dragonflies.', 'anatomy', 'intermediate', 0, 5, 70.00, 15, NULL, 1, '2025-10-04 14:41:03', '2025-10-04 14:41:03'),
(86, 'Protecting Pollinators: Bee Conservation', 'Learn about threats to bee populations and conservation efforts.', 'conservation', 'intermediate', 0, 6, 75.00, 25, NULL, 1, '2025-10-04 14:41:03', '2025-10-04 14:41:03'),
(87, 'Mosquito Biology and Disease', 'Understanding mosquito life cycles and their role in disease transmission.', 'behavior', 'advanced', 0, 5, 80.00, 20, NULL, 1, '2025-10-04 14:41:03', '2025-10-04 14:41:03'),
(88, 'Identifying Grasshoppers and Crickets', 'Learn to distinguish between grasshoppers, crickets, and katydids.', 'identification', 'beginner', 0, 5, 65.00, 15, NULL, 1, '2025-10-04 14:41:03', '2025-10-04 14:41:03'),
(89, 'Masters of Disguise: Insect Camouflage', 'Explore the amazing ways insects use camouflage and mimicry to survive.', 'behavior', 'beginner', 0, 6, 70.00, 20, NULL, 1, '2025-10-04 14:41:04', '2025-10-04 14:41:04'),
(90, 'The Science of Firefly Light', 'Discover how fireflies produce light and communicate through bioluminescence.', 'anatomy', 'advanced', 0, 5, 75.00, 20, NULL, 1, '2025-10-04 14:41:04', '2025-10-04 14:41:04');

-- --------------------------------------------------------

--
-- Table structure for table `assessment_questions`
--

CREATE TABLE `assessment_questions` (
  `id` int(11) NOT NULL,
  `assessment_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('multiple_choice','true_false','short_answer') DEFAULT 'multiple_choice',
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `correct_answer` text NOT NULL,
  `explanation` text DEFAULT NULL,
  `points` int(11) DEFAULT 1,
  `question_order` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assessment_questions`
--

INSERT INTO `assessment_questions` (`id`, `assessment_id`, `question_text`, `question_type`, `options`, `correct_answer`, `explanation`, `points`, `question_order`, `created_at`) VALUES
(1, 81, 'What are the four stages of a butterfly\'s life cycle?', 'multiple_choice', '[\"Egg, larva, pupa, adult\", \"Egg, caterpillar, cocoon, butterfly\", \"Larva, nymph, pupa, imago\", \"Egg, nymph, adult, death\"]', 'Egg, larva, pupa, adult', 'Butterflies undergo complete metamorphosis with four distinct stages: egg, larva (caterpillar), pupa (chrysalis), and adult.', 1, 1, '2025-10-04 14:41:02'),
(2, 81, 'How many legs does an adult butterfly have?', 'multiple_choice', '[\"4\", \"6\", \"8\", \"10\"]', '6', 'All insects, including butterflies, have six legs. Some butterflies hold their front legs close to their body, making them appear to have only four legs.', 1, 2, '2025-10-04 14:41:02'),
(3, 81, 'Butterflies taste with their feet.', 'true_false', '[]', 'True', 'Butterflies have taste receptors on their feet that help them identify suitable plants for laying eggs and feeding.', 1, 3, '2025-10-04 14:41:02'),
(4, 81, 'What is the main function of butterfly wing scales?', 'multiple_choice', '[\"Temperature regulation\", \"Color and protection\", \"Sound production\", \"Flight stability\"]', 'Color and protection', 'Butterfly wing scales create their vibrant colors and patterns, and also provide protection and help regulate temperature.', 1, 4, '2025-10-04 14:41:02'),
(5, 81, 'What do adult butterflies primarily feed on?', 'short_answer', '[]', 'nectar', 'Adult butterflies feed primarily on flower nectar, which provides them with energy. Some also feed on rotting fruit, tree sap, or dissolved minerals.', 1, 5, '2025-10-04 14:41:02'),
(6, 82, 'What is the scientific order name for beetles?', 'multiple_choice', '[\"Lepidoptera\", \"Coleoptera\", \"Hymenoptera\", \"Diptera\"]', 'Coleoptera', 'Coleoptera is the order containing beetles. The name comes from Greek words meaning \"sheath wing,\" referring to their hardened front wings.', 1, 1, '2025-10-04 14:41:02'),
(7, 82, 'Approximately how many beetle species have been identified worldwide?', 'multiple_choice', '[\"40,000\", \"150,000\", \"400,000\", \"1,000,000\"]', '400,000', 'Over 400,000 beetle species have been identified, representing about 40% of all known insect species and 25% of all known animal species.', 1, 2, '2025-10-04 14:41:02'),
(8, 82, 'What are the hardened front wings of beetles called?', 'short_answer', '[]', 'elytra', 'The elytra are the hardened front wings that protect the delicate hind wings used for flight. They meet in a straight line down the back.', 1, 3, '2025-10-04 14:41:02'),
(9, 82, 'All beetles can fly.', 'true_false', '[]', 'False', 'While most beetles can fly, some species have fused elytra or reduced wings and cannot fly. Examples include many ground beetles and darkling beetles.', 1, 4, '2025-10-04 14:41:02'),
(10, 82, 'Which beetle is considered beneficial for gardens by eating aphids?', 'multiple_choice', '[\"Japanese beetle\", \"Ladybug (Lady beetle)\", \"June beetle\", \"Stag beetle\"]', 'Ladybug (Lady beetle)', 'Ladybugs are voracious predators of aphids and other soft-bodied pests, making them valuable allies in gardens and agriculture.', 1, 5, '2025-10-04 14:41:02'),
(11, 83, 'What is the primary role of the queen ant in a colony?', 'multiple_choice', '[\"Hunting for food\", \"Defending the colony\", \"Laying eggs\", \"Building the nest\"]', 'Laying eggs', 'The queen\'s main function is reproduction - laying eggs to maintain and grow the colony population. She can live for many years.', 1, 1, '2025-10-04 14:41:02'),
(12, 83, 'Male ants die shortly after mating.', 'true_false', '[]', 'True', 'Male ants\' only purpose is reproduction. After the nuptial flight and mating, they typically die within a few days.', 1, 2, '2025-10-04 14:41:02'),
(13, 83, 'How do ants primarily communicate with each other?', 'multiple_choice', '[\"Sound vibrations\", \"Chemical pheromones\", \"Visual signals\", \"Touch only\"]', 'Chemical pheromones', 'Ants use chemical signals called pheromones to communicate about food sources, danger, and navigation. They also use touch and some sound.', 1, 3, '2025-10-04 14:41:02'),
(14, 83, 'What percentage of an ant colony\'s population are typically workers?', 'multiple_choice', '[\"25%\", \"50%\", \"75%\", \"90-95%\"]', '90-95%', 'The vast majority of ants in a colony are sterile female workers. Only a small percentage are reproductive individuals (queens and males).', 1, 4, '2025-10-04 14:41:02'),
(15, 83, 'Some ant species farm and cultivate fungus for food.', 'true_false', '[]', 'True', 'Leafcutter ants are famous fungus farmers. They cut leaves, carry them to their nest, and use them to cultivate fungus gardens for food.', 1, 5, '2025-10-04 14:41:02'),
(16, 83, 'What is the approximate weight an ant can carry relative to its body weight?', 'multiple_choice', '[\"Equal to its weight\", \"5 times its weight\", \"10-50 times its weight\", \"100 times its weight\"]', '10-50 times its weight', 'Ants can carry 10 to 50 times their own body weight, depending on the species. Some can even carry more under certain conditions.', 1, 6, '2025-10-04 14:41:02'),
(17, 84, 'What percentage of flowering plants depend on animal pollinators, primarily insects?', 'multiple_choice', '[\"30%\", \"50%\", \"75%\", \"90%\"]', '90%', 'Approximately 90% of flowering plant species rely on animal pollinators, with insects (especially bees) being the most important group.', 1, 1, '2025-10-04 14:41:03'),
(18, 84, 'Which insects are known as nature\'s recyclers for breaking down dead organic matter?', 'multiple_choice', '[\"Butterflies\", \"Beetles and flies\", \"Dragonflies\", \"Grasshoppers\"]', 'Beetles and flies', 'Carrion beetles, dung beetles, and various fly larvae play crucial roles in decomposition and nutrient cycling.', 1, 2, '2025-10-04 14:41:03'),
(19, 84, 'Insects make up the majority of animal biodiversity on Earth.', 'true_false', '[]', 'True', 'Insects represent over 80% of all known animal species, making them by far the most diverse group of animals on the planet.', 1, 3, '2025-10-04 14:41:03'),
(20, 84, 'What ecosystem service do parasitic wasps primarily provide?', 'multiple_choice', '[\"Pollination\", \"Decomposition\", \"Natural pest control\", \"Soil aeration\"]', 'Natural pest control', 'Parasitic wasps lay eggs in or on other insects, controlling pest populations naturally without the need for chemical pesticides.', 1, 4, '2025-10-04 14:41:03'),
(21, 84, 'Why are dung beetles important for grassland ecosystems?', 'short_answer', '[]', 'nutrient cycling', 'Dung beetles bury animal waste, recycling nutrients back into the soil, improving soil structure, reducing parasites, and supporting plant growth.', 1, 5, '2025-10-04 14:41:03'),
(22, 85, 'How many wings does a dragonfly have?', 'multiple_choice', '[\"2\", \"4\", \"6\", \"8\"]', '4', 'Dragonflies have four wings that can move independently, allowing for exceptional flight control and maneuverability.', 1, 1, '2025-10-04 14:41:03'),
(23, 85, 'Dragonflies can fly backwards.', 'true_false', '[]', 'True', 'Dragonflies are among the few insects that can fly backwards, hover, and make sharp turns due to their independently moving wings.', 1, 2, '2025-10-04 14:41:03'),
(24, 85, 'What percentage of a dragonfly\'s head is comprised of its eyes?', 'multiple_choice', '[\"25%\", \"50%\", \"75%\", \"Nearly 100%\"]', 'Nearly 100%', 'Dragonfly heads are almost entirely dominated by their compound eyes, which can contain up to 30,000 individual lenses each.', 1, 3, '2025-10-04 14:41:03'),
(25, 85, 'What is the maximum speed a dragonfly can fly?', 'multiple_choice', '[\"10 mph\", \"20 mph\", \"35 mph\", \"50 mph\"]', '35 mph', 'Dragonflies can reach speeds of about 35 mph, making them one of the fastest flying insects. They can also accelerate very quickly.', 1, 4, '2025-10-04 14:41:03'),
(26, 85, 'In which life stage do dragonflies spend most of their life?', 'multiple_choice', '[\"Egg\", \"Aquatic nymph\", \"Adult\", \"Pupa\"]', 'Aquatic nymph', 'Dragonflies spend most of their life (months to years) as aquatic nymphs. The adult flying stage typically lasts only a few weeks to months.', 1, 5, '2025-10-04 14:41:03'),
(27, 86, 'What is Colony Collapse Disorder (CCD)?', 'multiple_choice', '[\"A bee disease\", \"A phenomenon where worker bees abandon the hive\", \"A parasitic infection\", \"A genetic mutation\"]', 'A phenomenon where worker bees abandon the hive', 'CCD is characterized by the sudden disappearance of worker bees from a colony, leaving behind the queen and food stores.', 1, 1, '2025-10-04 14:41:03'),
(28, 86, 'Which of the following is NOT a major threat to bee populations?', 'multiple_choice', '[\"Pesticides\", \"Habitat loss\", \"Predatory birds\", \"Climate change\"]', 'Predatory birds', 'While predation exists naturally, the main threats are pesticides (especially neonicotinoids), habitat loss, diseases, and climate change.', 1, 2, '2025-10-04 14:41:03'),
(29, 86, 'Native wild bees are generally better pollinators of native plants than honey bees.', 'true_false', '[]', 'True', 'Native bees have co-evolved with native plants and are often more efficient pollinators for them than introduced honey bees.', 1, 3, '2025-10-04 14:41:03'),
(30, 86, 'What percentage of global food crops rely on bee pollination?', 'multiple_choice', '[\"10%\", \"25%\", \"35%\", \"50%\"]', '35%', 'About 35% of global food production depends on pollinators, with bees being the most important group. This includes many fruits, vegetables, and nuts.', 1, 4, '2025-10-04 14:41:03'),
(31, 86, 'What can homeowners do to help support bee populations?', 'short_answer', '[]', 'plant native flowers', 'Planting native flowers, avoiding pesticides, providing nesting sites, and maintaining some natural areas all help support bee populations.', 1, 5, '2025-10-04 14:41:03'),
(32, 86, 'All bees live in hives with a queen.', 'true_false', '[]', 'False', 'Most bee species are actually solitary - they don\'t live in colonies. Only some species like honey bees and bumblebees are social.', 1, 6, '2025-10-04 14:41:03'),
(33, 87, 'Which mosquitoes bite humans?', 'multiple_choice', '[\"Only males\", \"Only females\", \"Both males and females\", \"Neither, they feed on nectar\"]', 'Only females', 'Female mosquitoes need blood meals for egg development. Males feed exclusively on nectar and plant juices.', 1, 1, '2025-10-04 14:41:03'),
(34, 87, 'Mosquito larvae must develop in water.', 'true_false', '[]', 'True', 'All mosquito species require water for their aquatic larval and pupal stages. Even small amounts of standing water can support development.', 1, 2, '2025-10-04 14:41:03'),
(35, 87, 'Which disease is NOT transmitted by mosquitoes?', 'multiple_choice', '[\"Malaria\", \"Dengue fever\", \"Lyme disease\", \"Zika virus\"]', 'Lyme disease', 'Lyme disease is transmitted by ticks, not mosquitoes. Mosquitoes transmit malaria, dengue, Zika, yellow fever, and West Nile virus among others.', 1, 3, '2025-10-04 14:41:03'),
(36, 87, 'What is the primary way mosquitoes locate their hosts?', 'multiple_choice', '[\"Vision\", \"Carbon dioxide and body heat\", \"Sound\", \"Moisture\"]', 'Carbon dioxide and body heat', 'Mosquitoes detect carbon dioxide from breath and body heat to locate hosts. They can detect CO2 from up to 100 feet away.', 1, 4, '2025-10-04 14:41:03'),
(37, 87, 'How long does it typically take for a mosquito to complete its life cycle?', 'multiple_choice', '[\"3-5 days\", \"1-2 weeks\", \"1 month\", \"3 months\"]', '1-2 weeks', 'Under optimal conditions, mosquitoes can complete their life cycle (egg to adult) in 7-14 days, though this varies by species and temperature.', 1, 5, '2025-10-04 14:41:03'),
(38, 88, 'What is the main difference between grasshopper and cricket antennae?', 'multiple_choice', '[\"Color\", \"Grasshoppers have short antennae, crickets have long antennae\", \"Number of segments\", \"Crickets have no antennae\"]', 'Grasshoppers have short antennae, crickets have long antennae', 'Grasshoppers have short, thick antennae while crickets and katydids have long, thread-like antennae often longer than their body.', 1, 1, '2025-10-04 14:41:04'),
(39, 88, 'Crickets produce their chirping sound by rubbing their wings together.', 'true_false', '[]', 'True', 'Male crickets chirp by rubbing specialized structures on their wings together in a process called stridulation. Different chirp patterns signal different messages.', 1, 2, '2025-10-04 14:41:04'),
(40, 88, 'What order do grasshoppers, crickets, and katydids belong to?', 'short_answer', '[]', 'Orthoptera', 'These insects belong to the order Orthoptera, which means \"straight wings.\" The order includes over 20,000 species worldwide.', 1, 3, '2025-10-04 14:41:04'),
(41, 88, 'When are crickets most active?', 'multiple_choice', '[\"Early morning\", \"Midday\", \"Evening and night\", \"They are equally active all day\"]', 'Evening and night', 'Most cricket species are nocturnal, becoming active at dusk. Grasshoppers are typically diurnal (active during the day).', 1, 4, '2025-10-04 14:41:04'),
(42, 88, 'How do grasshoppers differ from locusts?', 'multiple_choice', '[\"They are completely different species\", \"Locusts are a type of grasshopper that can swarm\", \"Locusts are larger\", \"Grasshoppers can fly, locusts cannot\"]', 'Locusts are a type of grasshopper that can swarm', 'Locusts are actually grasshoppers that can enter a \"gregarious phase\" under certain conditions, forming massive destructive swarms.', 1, 5, '2025-10-04 14:41:04'),
(43, 89, 'What is the difference between camouflage and mimicry?', 'multiple_choice', '[\"They are the same thing\", \"Camouflage is blending in, mimicry is resembling another organism\", \"Camouflage is for hiding, mimicry is for attracting mates\", \"Mimicry only occurs in butterflies\"]', 'Camouflage is blending in, mimicry is resembling another organism', 'Camouflage helps insects blend with their environment. Mimicry is when insects resemble other organisms, often dangerous ones, for protection.', 1, 1, '2025-10-04 14:41:04'),
(44, 89, 'Which insect is famous for looking exactly like a leaf?', 'multiple_choice', '[\"Praying mantis\", \"Walking stick\", \"Leaf insect\", \"Cicada\"]', 'Leaf insect', 'Leaf insects (Phylliidae family) are masters of mimicry, with flat bodies, leaf-like wings, and even vein patterns that make them nearly indistinguishable from leaves.', 1, 2, '2025-10-04 14:41:04'),
(45, 89, 'Some harmless insects evolve to look like dangerous insects. What is this called?', 'short_answer', '[]', 'Batesian mimicry', 'Batesian mimicry occurs when a harmless species evolves to resemble a harmful or dangerous species, gaining protection from predators.', 1, 3, '2025-10-04 14:41:04'),
(46, 89, 'The false eyespots on some butterfly wings serve no purpose.', 'true_false', '[]', 'False', 'False eyespots (ocelli) can startle predators or deflect attacks away from vital body parts. Some eyespots even mimic the eyes of larger predators.', 1, 4, '2025-10-04 14:41:04'),
(47, 89, 'What type of insect mimics tree bark to hide from predators?', 'multiple_choice', '[\"Ladybug\", \"Peppered moth\", \"Honey bee\", \"Firefly\"]', 'Peppered moth', 'Peppered moths are famous for their camouflage against tree bark. Their coloration even evolved in response to industrial pollution changing bark color.', 1, 5, '2025-10-04 14:41:04'),
(48, 89, 'Some insects can change color to match their environment.', 'true_false', '[]', 'True', 'Some insects, like certain treehoppers and stick insects, can gradually change color over days to weeks to better match their surroundings.', 1, 6, '2025-10-04 14:41:04'),
(49, 90, 'What is the primary purpose of firefly bioluminescence?', 'multiple_choice', '[\"Finding food\", \"Mating communication\", \"Defense against predators\", \"Temperature regulation\"]', 'Mating communication', 'Fireflies use their light primarily for courtship. Males fly while flashing patterns, and females respond with their own flashes if interested.', 1, 1, '2025-10-04 14:41:04'),
(50, 90, 'What chemical reaction produces light in fireflies?', 'multiple_choice', '[\"Photosynthesis\", \"Oxidation of luciferin\", \"Phosphorescence\", \"Chlorophyll breakdown\"]', 'Oxidation of luciferin', 'Firefly light is produced when the chemical luciferin reacts with oxygen in the presence of the enzyme luciferase, producing light with minimal heat.', 1, 2, '2025-10-04 14:41:04'),
(51, 90, 'Firefly light is a \"cold light\" that produces almost no heat.', 'true_false', '[]', 'True', 'Firefly bioluminescence is nearly 100% efficient, producing light with almost no waste heat. This is far more efficient than any artificial light source.', 1, 3, '2025-10-04 14:41:04'),
(52, 90, 'What color is most common for firefly bioluminescence?', 'multiple_choice', '[\"Blue\", \"Green\", \"Yellow-green\", \"Red\"]', 'Yellow-green', 'Most firefly species produce yellow-green light, though some species can produce yellow, orange, or even blue-green light depending on their luciferin chemistry.', 1, 4, '2025-10-04 14:41:04'),
(53, 90, 'Some firefly species are predatory and use light signals to lure prey.', 'true_false', '[]', 'True', 'Females of some Photuris firefly species mimic the flash patterns of other species to lure males, which they then capture and eat (aggressive mimicry).', 1, 5, '2025-10-04 14:41:04');

-- --------------------------------------------------------

--
-- Table structure for table `badges`
--

CREATE TABLE `badges` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `icon` varchar(10) DEFAULT NULL,
  `category` enum('reading','knowledge','exploration','social','special') DEFAULT 'reading',
  `requirements` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`requirements`)),
  `criteria_type` varchar(50) DEFAULT NULL,
  `criteria_value` int(11) DEFAULT 0,
  `points` int(11) DEFAULT 10,
  `rarity` enum('common','uncommon','rare','epic','legendary','cardinal') DEFAULT 'common',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `badges`
--

INSERT INTO `badges` (`id`, `name`, `description`, `icon`, `category`, `requirements`, `criteria_type`, `criteria_value`, `points`, `rarity`, `is_active`, `created_at`, `updated_at`) VALUES
(2, 'King Of Insect', 'THE CREATOR', '👑', 'special', '{\"description\":\"SECRET\"}', NULL, 0, 1000000, 'cardinal', 1, '2025-10-04 14:06:43', '2025-10-04 16:46:47'),
(4, 'Fairy Servant', 'Butterfly', '🦋', 'reading', '{\"description\":\"Read about Butterfly\"}', NULL, 0, 10, 'common', 1, '2025-10-04 14:27:04', '2025-10-04 14:27:04'),
(5, 'First Steps', 'Read your first article about insects and begin your learning journey.', '🐛', 'reading', '{\"description\": \"Complete reading 1 article\"}', NULL, 0, 10, 'common', 1, '2025-10-04 14:43:44', '2025-10-04 14:43:44'),
(6, 'Curious Reader', 'You\'re getting the hang of this! Read 5 articles to earn this badge.', '📖', 'reading', '{\"description\": \"Complete reading 5 articles\"}', NULL, 0, 25, 'common', 1, '2025-10-04 14:43:44', '2025-10-04 14:43:44'),
(7, 'Bookworm', 'A true enthusiast! Read 25 articles about the insect world.', '🐞', 'reading', '{\"description\": \"Complete reading 25 articles\"}', NULL, 0, 100, 'uncommon', 1, '2025-10-04 14:43:44', '2025-10-04 14:43:44'),
(8, 'Encyclopedia', 'Master reader! You\'ve read 50 articles and gained extensive knowledge.', '📚', 'reading', '{\"description\": \"Complete reading 50 articles\"}', NULL, 0, 250, 'rare', 1, '2025-10-04 14:43:44', '2025-10-04 14:43:44'),
(9, 'Speed Reader', 'Complete 5 articles in a single day. Your dedication is impressive!', '⚡', 'reading', '{\"description\": \"Read 5 articles in one day\"}', NULL, 0, 50, 'uncommon', 1, '2025-10-04 14:43:44', '2025-10-04 14:43:44'),
(10, 'Quiz Master', 'Pass your first assessment with flying colors!', '🎯', 'knowledge', '{\"description\": \"Pass 1 assessment with 70% or higher\"}', NULL, 0, 20, 'common', 1, '2025-10-04 14:43:44', '2025-10-04 14:43:44'),
(11, 'Insect Scholar', 'Pass 5 assessments and prove your growing expertise.', '🎓', 'knowledge', '{\"description\": \"Pass 5 assessments\"}', NULL, 0, 75, 'uncommon', 1, '2025-10-04 14:43:44', '2025-10-04 14:43:44'),
(12, 'Perfectionist', 'Achieve 100% on any assessment. Flawless knowledge!', '💎', 'knowledge', '{\"description\": \"Score 100% on any assessment\"}', NULL, 0, 150, 'epic', 1, '2025-10-04 14:43:44', '2025-10-04 14:43:44'),
(13, 'Entomologist', 'Pass 10 assessments and join the ranks of insect experts.', '🔬', 'knowledge', '{\"description\": \"Pass 10 assessments\"}', NULL, 0, 200, 'rare', 1, '2025-10-04 14:43:44', '2025-10-04 14:43:44'),
(14, 'Quick Thinker', 'Complete an assessment in under 5 minutes with a passing score.', '💨', 'knowledge', '{\"description\": \"Pass an assessment in less than 5 minutes\"}', NULL, 0, 100, 'rare', 1, '2025-10-04 14:43:44', '2025-10-04 14:43:44'),
(15, 'Butterfly Enthusiast', 'Read 5 articles specifically about butterflies.', '🦋', 'exploration', '{\"description\": \"Read 5 articles in the Lepidoptera category\"}', NULL, 0, 30, 'common', 1, '2025-10-04 14:43:44', '2025-10-04 14:43:44'),
(16, 'Beetle Hunter', 'Explore the diverse world of beetles by reading 5 beetle articles.', '🪲', 'exploration', '{\"description\": \"Read 5 articles about Coleoptera\"}', NULL, 0, 30, 'common', 1, '2025-10-04 14:43:44', '2025-10-04 14:43:44'),
(17, 'Diversity Champion', 'Explore all major insect orders by reading articles from 5 different orders.', '🌈', 'exploration', '{\"description\": \"Read articles from 5 different insect orders\"}', NULL, 0, 100, 'uncommon', 1, '2025-10-04 14:43:44', '2025-10-04 14:43:44'),
(18, 'Conservation Hero', 'Read 3 articles about insect conservation and learn how to help.', '🌱', 'exploration', '{\"description\": \"Read 3 conservation articles\"}', NULL, 0, 50, 'uncommon', 1, '2025-10-04 14:43:44', '2025-10-04 14:43:44'),
(19, 'First Comment', 'Join the conversation! Leave your first comment on an article.', '💬', 'social', '{\"description\": \"Post 1 comment\"}', NULL, 0, 15, 'common', 1, '2025-10-04 14:43:44', '2025-10-04 14:43:44'),
(20, 'Contributor', 'Share your thoughts! Post 10 comments on various articles.', '✍️', 'social', '{\"description\": \"Post 10 comments\"}', NULL, 0, 50, 'uncommon', 1, '2025-10-04 14:43:44', '2025-10-04 14:43:44'),
(21, 'Community Voice', 'Active participant! Post 25 comments and engage with the community.', '📣', 'social', '{\"description\": \"Post 25 comments\"}', NULL, 0, 150, 'rare', 1, '2025-10-04 14:43:44', '2025-10-04 14:43:44'),
(22, 'Week Warrior', 'Login and read articles for 7 consecutive days. Dedication!', '🔥', 'special', '{\"description\": \"7-day login streak\"}', NULL, 0, 75, 'uncommon', 1, '2025-10-04 14:43:44', '2025-10-04 14:43:44'),
(23, 'Completionist', 'Pass all available assessments with 80% or higher. Ultimate achievement!', '📚', 'special', '{\"description\":\"Pass all assessments with 80%+\"}', NULL, 0, 500, 'legendary', 1, '2025-10-04 14:43:44', '2025-10-04 14:44:28'),
(24, 'Insect Life Legend', 'Reach level 10 by earning 1000 total points. You are a true expert!', '⭐', 'special', '{\"description\": \"Earn 1000 total points\"}', NULL, 0, 1000, 'legendary', 1, '2025-10-04 14:43:44', '2025-10-04 14:43:44');

-- --------------------------------------------------------

--
-- Table structure for table `bookmarks`
--

CREATE TABLE `bookmarks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bookmarks`
--

INSERT INTO `bookmarks` (`id`, `user_id`, `article_id`, `notes`, `created_at`) VALUES
(5, 3, 540, NULL, '2025-10-04 17:57:36');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `is_approved` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `article_id`, `user_id`, `parent_id`, `content`, `is_approved`, `created_at`, `updated_at`) VALUES
(4, 539, 4, NULL, 'wow', 1, '2025-10-04 17:58:57', '2025-10-04 17:58:57');

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_subscriptions`
--

CREATE TABLE `newsletter_subscriptions` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `unsubscribed_at` timestamp NULL DEFAULT NULL,
  `preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferences`)),
  `verification_token` varchar(100) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `newsletter_subscriptions`
--

INSERT INTO `newsletter_subscriptions` (`id`, `email`, `user_id`, `subscribed_at`, `unsubscribed_at`, `preferences`, `verification_token`, `is_verified`) VALUES
(1, 'jaybondholliete1@gmail.com', NULL, '2025-09-22 13:15:21', NULL, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `reading_progress`
--

CREATE TABLE `reading_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `progress_percentage` decimal(5,2) DEFAULT 0.00,
  `last_position` text DEFAULT NULL,
  `total_time_spent` int(11) DEFAULT 0,
  `first_read_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_read_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reading_progress`
--

INSERT INTO `reading_progress` (`id`, `user_id`, `article_id`, `progress_percentage`, `last_position`, `total_time_spent`, `first_read_at`, `last_read_at`) VALUES
(44, 3, 539, 100.00, NULL, 439, '2025-10-04 15:10:07', '2025-10-04 17:59:27'),
(47, 3, 540, 23.24, NULL, 26, '2025-10-04 15:13:15', '2025-10-04 17:57:35'),
(51, 3, 542, 1.00, NULL, 0, '2025-10-04 15:23:43', '2025-10-04 15:26:21'),
(54, 3, 545, 1.00, NULL, 0, '2025-10-04 16:26:26', '2025-10-04 16:26:26'),
(56, 4, 540, 1.00, NULL, 0, '2025-10-04 16:40:53', '2025-10-04 16:40:53'),
(57, 4, 539, 100.00, NULL, 14, '2025-10-04 16:41:44', '2025-10-04 17:59:00'),
(59, 4, 551, 1.00, NULL, 0, '2025-10-04 16:42:10', '2025-10-04 16:42:10'),
(60, 3, 548, 1.00, NULL, 0, '2025-10-04 16:43:25', '2025-10-04 16:43:25');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','integer','boolean','json') DEFAULT 'string',
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `is_public`, `updated_at`) VALUES
(1, 'site_maintenance', 'false', 'boolean', 'Enable maintenance mode', 0, '2025-09-21 19:10:18'),
(2, 'registration_enabled', 'true', 'boolean', 'Allow new user registrations', 1, '2025-09-21 19:10:18'),
(3, 'email_verification_required', 'true', 'boolean', 'Require email verification for new accounts', 0, '2025-09-21 19:10:18'),
(4, 'max_file_upload_size', '5242880', 'integer', 'Maximum file upload size in bytes (5MB)', 0, '2025-09-21 19:10:18'),
(5, 'articles_per_page', '12', 'integer', 'Number of articles per page', 1, '2025-09-21 19:10:18'),
(6, 'site_description', 'Explore the fascinating world of insects through our comprehensive digital library', 'string', 'Site meta description', 1, '2025-09-21 19:10:18'),
(7, 'contact_email', 'info@insectlife.com', 'string', 'Contact email address', 1, '2025-09-21 19:10:18');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `experience_level` enum('beginner','intermediate','advanced','expert') DEFAULT 'beginner',
  `user_type` enum('user','admin','moderator') DEFAULT 'user',
  `is_active` tinyint(1) DEFAULT 1,
  `email_verified` tinyint(1) DEFAULT 0,
  `email_verification_token` varchar(100) DEFAULT NULL,
  `password_reset_token` varchar(100) DEFAULT NULL,
  `password_reset_expires` datetime DEFAULT NULL,
  `newsletter_subscribed` tinyint(1) DEFAULT 0,
  `equipped_badge_id` int(11) DEFAULT NULL,
  `preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferences`)),
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `first_name`, `last_name`, `profile_image`, `experience_level`, `user_type`, `is_active`, `email_verified`, `email_verification_token`, `password_reset_token`, `password_reset_expires`, `newsletter_subscribed`, `equipped_badge_id`, `preferences`, `last_login`, `created_at`, `updated_at`) VALUES
(3, 'Zairee', 'jaybondholliete1@gmail.com', '$2y$10$N1QskiFpdhi5lWUNWtjQ5eES6zoMAcsftoK8IQ1BYb9Svh.B8Rhsq', 'Jaybond', 'Holliete', 'uploads/68d145d19ffc5_1758545361.png', 'expert', 'admin', 1, 0, 'af0075f869bc955dc97cb7a1bba044ec43f520a6e7e2859f62a868f4fc5a7da5', NULL, NULL, 1, 2, NULL, '2025-10-05 01:59:06', '2025-09-22 11:44:30', '2025-10-04 17:59:06'),
(4, 'ginre', 'mark@gmail.com', '$2y$10$y5kDYeRnaQhMLZlPdTxege6jEAAIpuh1n6dKheBkwdoAnYzBopheS', 'Mark', 'Villar', NULL, 'beginner', 'user', 1, 0, '65143b35279e95ee006430721040e0d2ed1496c4f9c86bd3207cde208b1be70a', NULL, NULL, 0, NULL, NULL, '2025-10-05 01:58:44', '2025-10-04 16:40:00', '2025-10-04 17:58:44');

-- --------------------------------------------------------

--
-- Table structure for table `user_assessments`
--

CREATE TABLE `user_assessments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `assessment_id` int(11) NOT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `total_questions` int(11) NOT NULL,
  `correct_answers` int(11) DEFAULT 0,
  `time_taken` int(11) DEFAULT NULL,
  `passed` tinyint(1) DEFAULT 0,
  `answers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`answers`)),
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_assessment_results`
--

CREATE TABLE `user_assessment_results` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `assessment_id` int(11) NOT NULL,
  `score` decimal(5,2) NOT NULL,
  `total_points` int(11) NOT NULL,
  `earned_points` int(11) NOT NULL,
  `passed` tinyint(1) DEFAULT 0,
  `time_taken` int(11) DEFAULT NULL,
  `answers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`answers`)),
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_badges`
--

CREATE TABLE `user_badges` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `badge_id` int(11) NOT NULL,
  `earned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `progress_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`progress_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_badges`
--

INSERT INTO `user_badges` (`id`, `user_id`, `badge_id`, `earned_at`, `progress_data`) VALUES
(1, 3, 2, '2025-10-04 14:11:41', NULL),
(2, 3, 5, '2025-10-04 16:50:43', NULL),
(3, 3, 6, '2025-10-04 16:50:48', NULL),
(4, 3, 7, '2025-10-04 16:50:53', NULL),
(5, 3, 8, '2025-10-04 16:50:59', NULL),
(6, 3, 9, '2025-10-04 16:51:07', NULL),
(7, 3, 10, '2025-10-04 16:51:25', NULL),
(8, 3, 11, '2025-10-04 16:53:15', NULL),
(9, 3, 12, '2025-10-04 16:53:24', NULL),
(10, 3, 13, '2025-10-04 16:53:34', NULL),
(11, 3, 14, '2025-10-04 16:53:41', NULL),
(12, 3, 15, '2025-10-04 16:53:49', NULL),
(13, 3, 16, '2025-10-04 16:53:55', NULL),
(14, 3, 17, '2025-10-04 16:54:07', NULL),
(15, 3, 18, '2025-10-04 16:54:16', NULL),
(16, 3, 19, '2025-10-04 16:54:26', NULL),
(17, 3, 20, '2025-10-04 16:54:35', NULL),
(18, 3, 21, '2025-10-04 16:54:43', NULL),
(19, 3, 22, '2025-10-04 16:54:48', NULL),
(20, 3, 23, '2025-10-04 16:54:53', NULL),
(21, 3, 24, '2025-10-04 16:54:58', NULL),
(22, 3, 4, '2025-10-04 16:55:07', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_interests`
--

CREATE TABLE `user_interests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `interest` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_interests`
--

INSERT INTO `user_interests` (`id`, `user_id`, `interest`, `created_at`) VALUES
(10, 3, 'butterflies', '2025-09-24 14:12:14'),
(11, 3, 'beetles', '2025-09-24 14:12:14'),
(12, 3, 'ants', '2025-09-24 14:12:14'),
(13, 3, 'bees', '2025-09-24 14:12:14'),
(14, 3, 'conservation', '2025-09-24 14:12:14'),
(15, 3, 'photography', '2025-09-24 14:12:14'),
(16, 3, 'research', '2025-09-24 14:12:15'),
(17, 3, 'gardening', '2025-09-24 14:12:15'),
(18, 4, 'ants', '2025-10-04 16:40:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_difficulty` (`difficulty_level`),
  ADD KEY `idx_order` (`insect_order`),
  ADD KEY `idx_author` (`author_id`),
  ADD KEY `idx_published` (`published_at`);
ALTER TABLE `articles` ADD FULLTEXT KEY `search_content` (`title`,`content`,`excerpt`);

--
-- Indexes for table `article_images`
--
ALTER TABLE `article_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_article_id` (`article_id`);

--
-- Indexes for table `article_ratings`
--
ALTER TABLE `article_ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_article_rating` (`user_id`,`article_id`),
  ADD KEY `idx_article_rating` (`article_id`,`rating`);

--
-- Indexes for table `assessments`
--
ALTER TABLE `assessments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_difficulty` (`difficulty_level`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `badge_id` (`badge_id`);

--
-- Indexes for table `assessment_questions`
--
ALTER TABLE `assessment_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_assessment_id` (`assessment_id`),
  ADD KEY `idx_question_order` (`question_order`);

--
-- Indexes for table `badges`
--
ALTER TABLE `badges`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `bookmarks`
--
ALTER TABLE `bookmarks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_article_bookmark` (`user_id`,`article_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_article_id` (`article_id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_article_id` (`article_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_parent_id` (`parent_id`),
  ADD KEY `idx_approved` (`is_approved`);

--
-- Indexes for table `newsletter_subscriptions`
--
ALTER TABLE `newsletter_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_verified` (`is_verified`);

--
-- Indexes for table `reading_progress`
--
ALTER TABLE `reading_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_article` (`user_id`,`article_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_article_id` (`article_id`),
  ADD KEY `idx_progress` (`progress_percentage`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_equipped_badge` (`equipped_badge_id`);

--
-- Indexes for table `user_assessments`
--
ALTER TABLE `user_assessments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_assessment_id` (`assessment_id`),
  ADD KEY `idx_score` (`score`);

--
-- Indexes for table `user_assessment_results`
--
ALTER TABLE `user_assessment_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assessment_id` (`assessment_id`),
  ADD KEY `idx_user_assessment` (`user_id`,`assessment_id`),
  ADD KEY `idx_completed_at` (`completed_at`);

--
-- Indexes for table `user_badges`
--
ALTER TABLE `user_badges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_badge` (`user_id`,`badge_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_badge_id` (`badge_id`);

--
-- Indexes for table `user_interests`
--
ALTER TABLE `user_interests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `articles`
--
ALTER TABLE `articles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=553;

--
-- AUTO_INCREMENT for table `article_images`
--
ALTER TABLE `article_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `article_ratings`
--
ALTER TABLE `article_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `assessments`
--
ALTER TABLE `assessments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT for table `assessment_questions`
--
ALTER TABLE `assessment_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `badges`
--
ALTER TABLE `badges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `bookmarks`
--
ALTER TABLE `bookmarks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `newsletter_subscriptions`
--
ALTER TABLE `newsletter_subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reading_progress`
--
ALTER TABLE `reading_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_assessments`
--
ALTER TABLE `user_assessments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_assessment_results`
--
ALTER TABLE `user_assessment_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_badges`
--
ALTER TABLE `user_badges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `user_interests`
--
ALTER TABLE `user_interests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `articles`
--
ALTER TABLE `articles`
  ADD CONSTRAINT `articles_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `article_images`
--
ALTER TABLE `article_images`
  ADD CONSTRAINT `article_images_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `article_ratings`
--
ALTER TABLE `article_ratings`
  ADD CONSTRAINT `article_ratings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `article_ratings_ibfk_2` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assessments`
--
ALTER TABLE `assessments`
  ADD CONSTRAINT `assessments_ibfk_1` FOREIGN KEY (`badge_id`) REFERENCES `badges` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `assessment_questions`
--
ALTER TABLE `assessment_questions`
  ADD CONSTRAINT `assessment_questions_ibfk_1` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bookmarks`
--
ALTER TABLE `bookmarks`
  ADD CONSTRAINT `bookmarks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookmarks_ibfk_2` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_3` FOREIGN KEY (`parent_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `newsletter_subscriptions`
--
ALTER TABLE `newsletter_subscriptions`
  ADD CONSTRAINT `newsletter_subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `reading_progress`
--
ALTER TABLE `reading_progress`
  ADD CONSTRAINT `reading_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reading_progress_ibfk_2` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_equipped_badge` FOREIGN KEY (`equipped_badge_id`) REFERENCES `badges` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `user_assessments`
--
ALTER TABLE `user_assessments`
  ADD CONSTRAINT `user_assessments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_assessments_ibfk_2` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_assessment_results`
--
ALTER TABLE `user_assessment_results`
  ADD CONSTRAINT `user_assessment_results_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_assessment_results_ibfk_2` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_badges`
--
ALTER TABLE `user_badges`
  ADD CONSTRAINT `user_badges_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_badges_ibfk_2` FOREIGN KEY (`badge_id`) REFERENCES `badges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_interests`
--
ALTER TABLE `user_interests`
  ADD CONSTRAINT `user_interests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
