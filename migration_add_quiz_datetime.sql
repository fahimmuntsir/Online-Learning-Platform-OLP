-- Run this SQL once in your database to add Quiz_Date and Quiz_Time columns
-- to the quiz_description table.

ALTER TABLE `quiz_description`
    ADD COLUMN IF NOT EXISTS `Quiz_Date` DATE    DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `Quiz_Time` TIME    DEFAULT NULL;
