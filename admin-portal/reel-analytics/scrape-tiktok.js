// =============================================
// TIKTOK SCRAPER - COMPLETE WORKING VERSION
// =============================================

const puppeteer = require('puppeteer');

async function scrapeTikTok(url) {
    console.log('🔍 Starting scrape for:', url);
    
    const browser = await puppeteer.launch({
        headless: true,
        executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe',
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu'
        ]
    });
    
    try {
        const page = await browser.newPage();
        await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        
        console.log('📄 Navigating to page...');
        await page.goto(url, { waitUntil: 'networkidle2', timeout: 60000 });
        await new Promise(resolve => setTimeout(resolve, 5000));
        
        console.log('📊 Extracting data...');
        
        const data = await page.evaluate(() => {
            const allText = document.body.textContent || '';
            const html = document.documentElement.innerHTML;
            
            // Get video ID
            const urlParts = window.location.href.split('/');
            const videoId = urlParts.find(part => /^[0-9]+$/.test(part)) || '';
            
            // Get username from URL
            const urlMatch = window.location.href.match(/@([a-zA-Z0-9_.]+)/);
            const username = urlMatch ? urlMatch[1] : 'unknown';
            
            // Extract stats
            const stats = { likes: 0, comments: 0, shares: 0, views: 0, followers: 0 };
            
            // Try JSON data first (most reliable)
            const jsonMatches = html.match(/"likeCount":(\d+)/i);
            if (jsonMatches) stats.likes = parseInt(jsonMatches[1]) || 0;
            
            const viewMatches = html.match(/"playCount":(\d+)/i);
            if (viewMatches) stats.views = parseInt(viewMatches[1]) || 0;
            
            const commentMatches = html.match(/"commentCount":(\d+)/i);
            if (commentMatches) stats.comments = parseInt(commentMatches[1]) || 0;
            
            const shareMatches = html.match(/"shareCount":(\d+)/i);
            if (shareMatches) stats.shares = parseInt(shareMatches[1]) || 0;
            
            // Fallback to text extraction if JSON failed
            if (stats.likes === 0) {
                const match = allText.match(/(\d+[.,]?\d*[KkMm]?)\s*(?:Like|like|❤️)/);
                if (match) {
                    let num = match[1].replace(/,/g, '');
                    if (num.includes('K')) stats.likes = parseFloat(num) * 1000;
                    else if (num.includes('M')) stats.likes = parseFloat(num) * 1000000;
                    else stats.likes = parseInt(num) || 0;
                }
            }
            
            // Get caption
            const captionEl = document.querySelector('[data-testid="video-desc"]') || 
                             document.querySelector('h1') ||
                             document.querySelector('[data-e2e="video-desc"]');
            const caption = captionEl ? captionEl.textContent.trim() : 'No caption';
            
            // Get thumbnail
            const video = document.querySelector('video');
            const thumbnail = video ? video.poster : '';
            
            // Get profile picture
            const avatar = document.querySelector('[data-testid="video-author-avatar"] img') ||
                          document.querySelector('img[src*="avatar"]');
            const profilePicture = avatar ? avatar.src : '';
            
            return {
                videoId: videoId,
                username: username,
                stats: stats,
                caption: caption,
                thumbnail: thumbnail,
                profilePicture: profilePicture,
                url: window.location.href
            };
        });
        
        console.log('✅ Data extracted successfully!');
        console.log('📊 Stats:', data.stats);
        console.log('👤 Username:', data.username);
        console.log('📝 Caption:', data.caption.substring(0, 60) + '...');
        
        await browser.close();
        return data;
        
    } catch (error) {
        console.error('❌ Error:', error.message);
        await browser.close();
        return null;
    }
}

const url = process.argv[2];
if (!url) {
    console.error('❌ Please provide a TikTok URL');
    process.exit(1);
}

scrapeTikTok(url)
    .then(data => {
        if (data) {
            console.log('\n✅ SUCCESS!');
            console.log(JSON.stringify(data, null, 2));
        } else {
            console.error('❌ Failed to scrape data');
            process.exit(1);
        }
    })
    .catch(error => {
        console.error('❌ Error:', error.message);
        process.exit(1);
    });