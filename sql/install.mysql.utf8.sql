-- Joomla AJAX Search - Database Installation
-- Version: 1.0.0
-- Compatible with Joomla 3.10+, 4.x, 5.x, 6.x

SET FOREIGN_KEY_CHECKS = 0;

-- Main search cache table
CREATE TABLE IF NOT EXISTS `of9kt_ajaxsearch_cache` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `query_hash` char(32) NOT NULL,
    `query_text` varchar(255) NOT NULL,
    `results` mediumtext NOT NULL,
    `hits` int(11) DEFAULT 0,
    `created` datetime NOT NULL,
    `expires` datetime NOT NULL,
    `language` char(7) DEFAULT '*',
    `user_group` varchar(255) DEFAULT '',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_query_hash_lang_group` (`query_hash`, `language`, `user_group`(100)),
    KEY `idx_expires` (`expires`),
    KEY `idx_created` (`created`),
    KEY `idx_hits` (`hits`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SP Page Builder content cache
CREATE TABLE IF NOT EXISTS `of9kt_ajaxsearch_sp_cache` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `sppagebuilder_id` int(11) NOT NULL,
    `parsed_content` mediumtext NOT NULL,
    `content_hash` varchar(32) NOT NULL,
    `parsed_date` datetime NOT NULL,
    `version` varchar(20) DEFAULT '',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_sppagebuilder_id` (`sppagebuilder_id`),
    KEY `idx_content_hash` (`content_hash`),
    KEY `idx_parsed_date` (`parsed_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Search analytics table
CREATE TABLE IF NOT EXISTS `of9kt_ajaxsearch_analytics` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `query` varchar(255) NOT NULL,
    `results_count` int(11) NOT NULL,
    `user_id` int(11) DEFAULT NULL,
    `session_id` varchar(128) DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text,
    `search_time` float DEFAULT NULL,
    `timestamp` datetime NOT NULL,
    `zero_results` tinyint(1) DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_query` (`query`(191)),
    KEY `idx_timestamp` (`timestamp`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_zero_results` (`zero_results`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configuration table
CREATE TABLE IF NOT EXISTS `of9kt_ajaxsearch_config` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `param_name` varchar(100) NOT NULL,
    `param_value` text,
    `param_type` varchar(20) DEFAULT 'string',
    `component` varchar(50) DEFAULT 'global',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_param_name_component` (`param_name`, `component`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default configuration
INSERT IGNORE INTO `of9kt_ajaxsearch_config` 
(`param_name`, `param_value`, `param_type`, `component`) VALUES
('cache_ttl', '300', 'int', 'global'),
('search_articles', '1', 'bool', 'global'),
('search_sppages', '1', 'bool', 'global'),
('search_customfields', '1', 'bool', 'global'),
('title_weight', '10', 'int', 'weights'),
('content_weight', '3', 'int', 'weights'),
('max_results', '50', 'int', 'global'),
('enable_analytics', '1', 'bool', 'analytics'),
('version', '1.0.0', 'string', 'system');

SET FOREIGN_KEY_CHECKS = 1;
