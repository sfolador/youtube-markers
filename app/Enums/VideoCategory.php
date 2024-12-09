<?php

namespace App\Enums;

enum VideoCategory: int
{
    case FilmAndAnimation = 1;
    case AutosAndVehicles = 2;
    case Music = 10;
    case PetsAndAnimals = 15;
    case Sports = 17;
    case ShortMovies = 18;
    case TravelAndEvents = 19;
    case Gaming = 20;
    case Videoblogging = 21;
    case PeopleAndBlogs = 22;
    case Comedy = 23;
    case Entertainment = 24;
    case NewsAndPolitics = 25;
    case HowtoAndStyle = 26;
    case Education = 27;
    case ScienceAndTechnology = 28;
    case NonprofitsAndActivism = 29;

    public function getDescription(): string
    {
        return match ($this) {
            self::FilmAndAnimation => 'Movies, TV shows, and other film content',
            self::AutosAndVehicles => 'Cars, motorcycles, and other vehicle-related content',
            self::Music => 'Music videos, songs, and musical content',
            self::PetsAndAnimals => 'Pet and wildlife videos',
            self::Sports => 'Sports footage, commentary, and athletic content',
            self::ShortMovies => 'Brief films and short-form content',
            self::TravelAndEvents => 'Travel vlogs, tourism, and event coverage',
            self::Gaming => 'Video game content, playthroughs, and gaming commentary',
            self::Videoblogging => 'Video diary entries and personal vlogs',
            self::PeopleAndBlogs => 'Personal content and lifestyle blogs',
            self::Comedy => 'Humorous and comedic content',
            self::Entertainment => 'General entertainment and variety content',
            self::NewsAndPolitics => 'Current events, political discussion, and news coverage',
            self::HowtoAndStyle => 'Tutorials, DIY guides, and fashion/beauty content',
            self::Education => 'Educational and instructional content',
            self::ScienceAndTechnology => 'Scientific discoveries, tech reviews, and innovations',
            self::NonprofitsAndActivism => 'Charitable causes and activist content'
        };
    }
}
