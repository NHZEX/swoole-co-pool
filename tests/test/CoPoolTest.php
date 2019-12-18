<?php
namespace Imi\Grpc\Test;

use PHPUnit\Framework\TestCase;
use Yurun\Swoole\CoPool\CoPool;
use Yurun\Swoole\CoPool\Interfaces\ICoTask;
use Yurun\Swoole\CoPool\Interfaces\ITaskParam;
use Swoole\Event;
use Swoole\Coroutine;

class CoPoolTest extends TestCase
{
    public function testTask()
    {
        $results = [];
        go(function() use(&$results){
            $coCount = 10; // 同时工作协程数，可以改小改大，看一下执行速度
            $queueLength = 1024; // 队列长度
            $pool = new CoPool($coCount, $queueLength,
                // 定义任务匿名类，当然你也可以定义成普通类，传入完整类名
                new class implements ICoTask
                {
                    /**
                     * 执行任务
                     *
                     * @param ITaskParam $param
                     * @return mixed
                     */
                    public function run(ITaskParam $param)
                    {
                        Coroutine::sleep(mt_rand(1, 100) / 1000);
                        return $param->getData(); // 返回任务执行结果，非必须
                    }

                }
            );
            // 运行协程池
            $pool->run();

            // 开始往协程池里推任务
            for($i = 1; $i <= 10; ++$i)
            {
                go(function() use($i, $pool, &$results){
                    for($j = 1; $j <= 10; ++$j)
                    {
                        // 增加任务
                        $result = $pool->addTask($i * $j);
                        $results[$i][$j] = $result;
                    }
                });
            }
        });
        Event::wait();
        $expected = [];
        for($i = 1; $i <= 10; ++$i)
        {
            for($j = 1; $j <= 10; ++$j)
            {
                $expected[$i][$j] = $i * $j;
            }
        }
        $this->assertEquals($expected, $results);
    }

    public function testTaskAsync()
    {
        $results = [];
        go(function() use(&$results){
            $coCount = 10; // 同时工作协程数，可以改小改大，看一下执行速度
            $queueLength = 1024; // 队列长度
            $pool = new CoPool($coCount, $queueLength,
                // 定义任务匿名类，当然你也可以定义成普通类，传入完整类名
                new class implements ICoTask
                {
                    /**
                     * 执行任务
                     *
                     * @param ITaskParam $param
                     * @return mixed
                     */
                    public function run(ITaskParam $param)
                    {
                        Coroutine::sleep(mt_rand(1, 100) / 1000);
                        return $param->getData(); // 返回任务执行结果，非必须
                    }
        
                }
            );
            // 运行协程池
            $pool->run();
        
            // 开始往协程池里推任务
            for($i = 1; $i <= 10; ++$i)
            {
                go(function() use($i, $pool, &$results){
                    for($j = 1; $j <= 10; ++$j)
                    {
                        // 增加任务，异步回调
                        $pool->addTaskAsync($i * $j
                        // 结束回调为非必须的
                        , function(ITaskParam $param, $result) use($i, $j, &$results){
                            $results[$i][$j] = $result;
                        });
                    }
                });
            }
        });
        Event::wait();
        $expected = [];
        for($i = 1; $i <= 10; ++$i)
        {
            for($j = 1; $j <= 10; ++$j)
            {
                $expected[$i][$j] = $i * $j;
            }
        }
        $this->assertEquals($expected, $results);
    }
}
