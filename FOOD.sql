-- MySQL dump 10.13  Distrib 8.0.44, for Win64 (x86_64)
--
-- Host: localhost    Database: foodfusion
-- ------------------------------------------------------
-- Server version	8.0.44

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `community_comments`
--

DROP TABLE IF EXISTS `community_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `community_comments` (
  `comment_id` int NOT NULL AUTO_INCREMENT,
  `post_id` int NOT NULL,
  `user_id` int NOT NULL,
  `comment` varchar(500) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`comment_id`),
  KEY `post_id` (`post_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `community_comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `community_posts` (`post_id`) ON DELETE CASCADE,
  CONSTRAINT `community_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_comments`
--

LOCK TABLES `community_comments` WRITE;
/*!40000 ALTER TABLE `community_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `community_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `community_likes`
--

DROP TABLE IF EXISTS `community_likes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `community_likes` (
  `like_id` int NOT NULL AUTO_INCREMENT,
  `post_id` int NOT NULL,
  `user_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`like_id`),
  UNIQUE KEY `unique_like` (`post_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `community_likes_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `community_posts` (`post_id`) ON DELETE CASCADE,
  CONSTRAINT `community_likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_likes`
--

LOCK TABLES `community_likes` WRITE;
/*!40000 ALTER TABLE `community_likes` DISABLE KEYS */;
/*!40000 ALTER TABLE `community_likes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `community_posts`
--

DROP TABLE IF EXISTS `community_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `community_posts` (
  `post_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` varchar(150) NOT NULL,
  `cuisine_type` varchar(60) NOT NULL,
  `dietary_pref` varchar(60) NOT NULL,
  `difficulty` enum('Easy','Medium','Hard') NOT NULL,
  `ingredients` text NOT NULL,
  `instructions` text NOT NULL,
  `cooking_tip` varchar(300) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'approved',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`post_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `community_posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_posts`
--

LOCK TABLES `community_posts` WRITE;
/*!40000 ALTER TABLE `community_posts` DISABLE KEYS */;
INSERT INTO `community_posts` VALUES (1,1,'Moms Secret Butter Chicken','Indian','Non-Vegetarian','Medium','Chicken, butter, cream, tomato puree, onion, garlic, ginger, spices','Marinate chicken in yogurt and spices for 2 hours. Cook onion, garlic and ginger until golden. Add tomato puree and spices. Add chicken and simmer for 20 minutes. Finish with butter and cream.','Always add cream off the heat for a silkier sauce.','approved','2026-04-03 05:19:20'),(2,1,'Crispy Masala Dosa','Indian','Vegetarian','Hard','Rice, urad dal, potato, onion, mustard seeds, curry leaves, turmeric','Soak rice and dal overnight. Grind to smooth batter. Ferment for 8 hours. Make potato filling with onions and spices. Spread batter thin on hot tawa. Add filling and fold.','The tawa must be very hot before you pour the batter.','approved','2026-04-03 05:19:20'),(3,1,'Homemade Margherita Pizza','Italian','Vegetarian','Medium','Pizza dough, tomato sauce, fresh mozzarella, basil, olive oil, salt','Preheat oven to 250 C. Stretch dough on floured surface. Spread tomato sauce. Add torn mozzarella. Bake 10 minutes until golden. Top with fresh basil and olive oil.','A pizza stone makes the base much crispier.','approved','2026-04-03 05:19:20'),(4,1,'Quick Egg Fried Rice','Chinese','Non-Vegetarian','Easy','Cooked rice, eggs, spring onion, soy sauce, sesame oil, garlic, peas','Heat wok until smoking. Add oil and garlic. Fry eggs scrambled. Add cold rice and break lumps. Add soy sauce and sesame oil. Toss on high flame for 3 minutes. Add spring onions.','Always use day-old cold rice for best texture.','approved','2026-04-03 05:19:20'),(5,1,'Mango Lassi','Beverage','Vegetarian','Easy','Ripe mango, yogurt, milk, sugar, cardamom, ice cubes','Blend mango pulp until smooth. Add yogurt, milk and sugar. Blend again until frothy. Add a pinch of cardamom. Pour over ice and serve chilled.','Use Alphonso mangoes for the best flavour.','approved','2026-04-03 05:19:20'),(6,9,'fried rice','Chinese','Non-Vegetarian','Medium','dqdd','dqdq','','approved','2026-04-03 05:36:36');
/*!40000 ALTER TABLE `community_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_messages`
--

DROP TABLE IF EXISTS `contact_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_messages` (
  `message_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `message` text,
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`message_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_messages`
--

LOCK TABLES `contact_messages` WRITE;
/*!40000 ALTER TABLE `contact_messages` DISABLE KEYS */;
INSERT INTO `contact_messages` VALUES (1,'rishmith poojary','rishmithpjy@gmail.com','[Subject: Community Cookbook]\nwdnjqdhq','2026-03-30 17:37:40'),(2,'rishmith poojary','rishmithpjy@gmail.com','[Subject: Community Cookbook]\nwdnjqdhq','2026-03-30 17:39:18'),(3,'admin','adim123@gmail.com','[Subject: Recipe Enquiry]\nhii','2026-04-07 04:28:39');
/*!40000 ALTER TABLE `contact_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `culinary_resources`
--

DROP TABLE IF EXISTS `culinary_resources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `culinary_resources` (
  `resource_id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `resource_type` enum('Recipe Card','Tutorial','Video') NOT NULL,
  `file_url` varchar(255) DEFAULT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`resource_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `culinary_resources`
--

LOCK TABLES `culinary_resources` WRITE;
/*!40000 ALTER TABLE `culinary_resources` DISABLE KEYS */;
INSERT INTO `culinary_resources` VALUES (1,'Fried Rice Tutorial','Video','https://youtu.be/hoZccEa0Pqo?si=RFFryVgTLxZLJl1A','How to make fried rice','2026-02-10 06:52:39'),(2,'Chicken Butter Massala','Video','https://youtu.be/lyBGqv7IWaQ?si=6RpLkEsiIOjr7yhz','How to make chicken butter massala at home','2026-04-03 05:12:58'),(3,'Chicken Biryani','Recipe Card','uploads\\Chicken Biryani.pdf','Chicken Biryani at Home','2026-04-03 05:15:20');
/*!40000 ALTER TABLE `culinary_resources` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `educational_resources`
--

DROP TABLE IF EXISTS `educational_resources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `educational_resources` (
  `resource_id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `resource_type` enum('PDF','Infographic','Video') NOT NULL,
  `file_url` varchar(255) DEFAULT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`resource_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `educational_resources`
--

LOCK TABLES `educational_resources` WRITE;
/*!40000 ALTER TABLE `educational_resources` DISABLE KEYS */;
INSERT INTO `educational_resources` VALUES (1,'Solar Energy Basics','PDF','uploads\\solar_timeline.pdf','Introduction to solar power','2026-02-10 06:52:49'),(2,'Renewable Energy Resources','PDF','uploads\\Renewable Energy Sources.pdf',NULL,'2026-03-31 07:27:27'),(3,'SOLAR PANNELS','Video','uploads\\7031083_Above_Aerial_3840x2160.mov',NULL,'2026-03-31 07:34:44');
/*!40000 ALTER TABLE `educational_resources` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recipes`
--

DROP TABLE IF EXISTS `recipes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recipes` (
  `recipe_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `title` varchar(150) NOT NULL,
  `cuisine_type` varchar(50) DEFAULT NULL,
  `dietary_preference` varchar(50) DEFAULT NULL,
  `difficulty` enum('Easy','Medium','Hard') DEFAULT NULL,
  `ingredients` text,
  `instructions` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `image_url` text,
  PRIMARY KEY (`recipe_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `recipes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recipes`
--

LOCK TABLES `recipes` WRITE;
/*!40000 ALTER TABLE `recipes` DISABLE KEYS */;
INSERT INTO `recipes` VALUES (1,1,'chciken Butter Masala','Indian','Vegetarian','Hard','Paneer, Butter, Tomato, Cream, Spices','Cook gravy → add paneer → simmer','2026-02-10 06:52:23','uploads/WhatsApp Image 2026-03-31 at 10.46.31.jpeg'),(2,NULL,'Paneer Butter Masala','Indian','Vegetarian','Medium','Paneer, butter, tomato puree, onion, cream, spices','Heat butter in a pan. Fry onions until golden. Add tomato puree and spices. Cook until oil separates. Add cream and mix well. Add paneer cubes. Simmer for 5 minutes. Serve hot.','2026-03-23 04:27:04','uploads\\paneer-butter-masala-5.jpeg'),(3,NULL,'Veg Fried Rice','Chinese','Vegetarian','Easy','Rice, carrot, capsicum, cabbage, soy sauce, garlic, oil','Boil rice and cool it. Heat oil in a wok. Add garlic and vegetables. Stir fry for 2 minutes. Add soy sauce. Add cooked rice and mix well. Cook for 3 minutes and serve.','2026-03-23 04:27:04','uploads\\WhatsApp Image 2026-03-31 at 12.21.20.jpeg'),(4,NULL,'Margherita Pizza','Italian','Vegetarian','Medium','Pizza base, tomato sauce, mozzarella cheese, basil, olive oil','Spread tomato sauce on pizza base. Add mozzarella cheese. Bake in preheated oven for 12 minutes. Add basil leaves and olive oil before serving.','2026-03-23 04:27:04','uploads\\WhatsApp Image 2026-03-31 at 12.21.55.jpeg'),(5,NULL,'Chocolate Cake','Dessert','Vegetarian','Medium','Flour, cocoa powder, sugar, eggs, butter, baking powder, milk','Mix dry ingredients. Add eggs, butter and milk. Mix into smooth batter. Pour into cake tin. Bake for 35 minutes. Cool and serve.','2026-03-23 04:27:04','uploads\\WhatsApp Image 2026-03-31 at 12.22.34.jpeg'),(6,NULL,'Pasta Alfredo','Italian','Vegetarian','Easy','Pasta, butter, cream, garlic, cheese, pepper','Boil pasta. Heat butter in a pan. Add garlic and cream. Add cheese and pepper. Mix well. Add pasta and coat evenly. Serve hot.','2026-03-23 04:27:04','uploads\\WhatsApp Image 2026-03-31 at 12.29.13.jpeg'),(7,NULL,'Chicken Biryani','Indian','Non-Vegetarian','Hard','Chicken, rice, onion, tomato, yogurt, spices, coriander','Minate chicken with yogurt and spices. Fry onions. Cook chicken mixture. Boil rice separately. Layer rice and chicken. Cook on low flame for 20 minutes. Serve hot.','2026-03-23 04:27:04','uploads\\WhatsApp Image 2026-03-31 at 12.29.58.jpeg'),(8,NULL,'Caesar Salad','American','Vegetarian','Easy','Lettuce, croutons, parmesan, mayonnaise, lemon juice, pepper','Wash lettuce and chop it. Mix mayonnaise, lemon juice and pepper for dressing. Toss lettuce with dressing. Add croutons and parmesan. Serve fresh.','2026-03-23 04:27:04','uploads\\WhatsApp Image 2026-03-31 at 12.30.52.jpeg'),(9,NULL,'Tacos','Mexican','Non-Vegetarian','Medium','Taco shells, chicken, onion, tomato, lettuce, cheese, sauce','Cook chicken with spices. Fill taco shells with chicken, onion, tomato and lettuce. Add cheese and sauce. Serve immediately.','2026-03-23 04:27:04','uploads\\WhatsApp Image 2026-03-31 at 12.31.32.jpeg'),(10,NULL,'Mango Smoothie','Beverage','Vegetarian','Easy','Mango, milk, sugar, ice cream, ice cubes','Add mango, milk, sugar and ice cream to blender. Blend until smooth. Pour into glass and serve chilled.','2026-03-23 04:27:04','uploads\\WhatsApp Image 2026-03-31 at 12.32.21.jpeg'),(11,NULL,'Grilled Sandwich','Snack','Vegetarian','Easy','Bread, butter, potato, onion, tomato, cheese, chutney','Spread butter and chutney on bread. Add sliced vegetables and cheese. Cover with another bread slice. Grill until golden brown. Serve hot.','2026-03-23 04:27:04','uploads\\WhatsApp Image 2026-03-31 at 12.34.36.jpeg');
/*!40000 ALTER TABLE `recipes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_activity`
--

DROP TABLE IF EXISTS `user_activity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_activity` (
  `activity_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `activity_type` varchar(50) DEFAULT NULL,
  `activity_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`activity_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_activity_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_activity`
--

LOCK TABLES `user_activity` WRITE;
/*!40000 ALTER TABLE `user_activity` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_activity` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `failed_attempts` int DEFAULT '0',
  `lock_until` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Rahul','rahul@gmail.com','5994471abb01112afcc18159f6cc74b4f511b99806da59b3caf5a9c173cacfc5',0,NULL,'2026-02-10 06:51:52'),(3,'rishmith','rishmithpjy@gmail.com','$2y$10$tjCcsmSbn7f7vRWU90nZTu85oagSMceuNyt7rf23HRly63btjAFMS',0,NULL,'2026-03-02 13:53:02'),(4,'rishmith','nithu@gmail.com','$2y$10$3lZHaj8fjMfnoJQeTamHUeIAghFC01t0.NMJeDK/H8EWokLakb1WC',0,NULL,'2026-03-04 13:48:45'),(9,'item','item@gmail.com','$2y$10$DO5JsX79prKE3qPCki7BE.MYMfHvqJ1qsqSOhFOSw.i1s0qBoharW',0,NULL,'2026-03-04 14:11:31');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-15 20:56:27
