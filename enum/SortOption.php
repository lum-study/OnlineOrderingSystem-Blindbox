<?php
enum SortOption: string
{
    case Newest = 'newest';
    case Popular = 'popular';
    case LowHigh = 'low-high';
    case HighLow = 'high-low';

    // Return the display title
    public function title(): string
    {
        return match($this) {
            self::Newest => 'Newest Arrivals',
            self::Popular => 'Most Popular',
            self::LowHigh => 'Price: Low to High',
            self::HighLow => 'Price: High to Low',
        };
    }
}
