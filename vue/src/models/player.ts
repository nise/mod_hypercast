import { CourseTextSnippet } from './courseTextSnippet';
import { LiveSession } from '@/models/liveSession';
import { WRTCSettings } from '@/models/wrtcSettings';

export interface Player {
    isPlaying: boolean;
    speed: number;
    volume: number;
    currentTime: number;
    duration: number;
    courseText: CourseTextSnippet[];
    chapters: CourseTextSnippet[];
    currentChapter: CourseTextSnippet;
    scrolledByUser: boolean;
    isSeeking: boolean;
    percentageSeeked: number;
    liveSessionJoined: boolean;
    liveSession: LiveSession;
    wrtcSetting: WRTCSettings;
    showChapterOverlay: boolean;
}
